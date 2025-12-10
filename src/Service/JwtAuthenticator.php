<?php

declare(strict_types=1);

namespace MethorZ\JwtAuthMiddleware\Service;

use Lcobucci\JWT\Encoding\CannotDecodeContent;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Token\UnsupportedHeaderFound;
use MethorZ\JwtAuthMiddleware\Contract\JwtAuthenticatorInterface;
use MethorZ\JwtAuthMiddleware\Contract\JwtValidatorInterface;
use MethorZ\JwtAuthMiddleware\Contract\TokenExtractorInterface;
use MethorZ\JwtAuthMiddleware\Exception\InsufficientScopeException;
use MethorZ\JwtAuthMiddleware\Exception\InvalidTokenException;
use MethorZ\JwtAuthMiddleware\Exception\MissingTokenException;
use Psr\Http\Message\ServerRequestInterface;

use function array_filter;
use function array_intersect;
use function explode;
use function trim;

/**
 * Main JWT authenticator service
 *
 * Coordinates token extraction, parsing, and validation
 */
final readonly class JwtAuthenticator implements JwtAuthenticatorInterface
{
    public function __construct(
        private TokenExtractorInterface $tokenExtractor,
        private JwtValidatorInterface $jwtValidator,
    ) {
    }

    public function authenticate(ServerRequestInterface $request): Plain
    {
        // Extract token string from request
        $tokenString = $this->tokenExtractor->extract($request);
        if ($tokenString === null) {
            throw MissingTokenException::fromRequest();
        }

        // Parse token
        $token = $this->parseToken($tokenString);

        // Validate token
        $this->jwtValidator->validate($token);

        return $token;
    }

    /**
     * @param array<string> $requiredScopes
     */
    public function validateScopes(Plain $token, array $requiredScopes): void
    {
        if ($requiredScopes === []) {
            return;
        }

        // Get scopes from token (can be space-separated string or array)
        $scopeClaim = $token->claims()->get('scope', '');
        $tokenScopes = $this->parseScopes($scopeClaim);

        // Check if all required scopes are present
        $matchingScopes = array_intersect($requiredScopes, $tokenScopes);

        if (count($matchingScopes) !== count($requiredScopes)) {
            throw InsufficientScopeException::missingScopes($requiredScopes, $tokenScopes);
        }
    }

    /**
     * Parse JWT token string into Plain token object
     */
    private function parseToken(string $tokenString): Plain
    {
        $parser = new Parser(new JoseEncoder());

        try {
            $token = $parser->parse($tokenString);
        } catch (CannotDecodeContent | InvalidTokenStructure | UnsupportedHeaderFound $e) {
            throw InvalidTokenException::malformed($e);
        }

        if (! $token instanceof Plain) {
            throw InvalidTokenException::invalidStructure();
        }

        return $token;
    }

    /**
     * Parse scope claim (handles both space-separated string and array)
     *
     * @return array<string>
     */
    private function parseScopes(mixed $scopeClaim): array
    {
        if ($scopeClaim === '' || $scopeClaim === null) {
            return [];
        }

        if (is_array($scopeClaim)) {
            return $scopeClaim;
        }

        if (is_string($scopeClaim)) {
            return array_filter(
                array_map(
                    fn(string $scope): string => trim($scope),
                    explode(' ', $scopeClaim),
                ),
                fn(string $scope): bool => $scope !== '',
            );
        }

        return [];
    }
}
