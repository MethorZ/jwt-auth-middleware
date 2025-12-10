<?php

declare(strict_types=1);

namespace MethorZ\JwtAuthMiddleware\Contract;

use Lcobucci\JWT\Token\Plain;
use MethorZ\JwtAuthMiddleware\Exception\JwtAuthenticationException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Main JWT authenticator service
 *
 * Coordinates token extraction, parsing, and validation
 */
interface JwtAuthenticatorInterface
{
    /**
     * Authenticate request and return validated token
     *
     * @throws JwtAuthenticationException When authentication fails
     */
    public function authenticate(ServerRequestInterface $request): Plain;

    /**
     * Validate token scopes against required scopes
     *
     * @param array<string> $requiredScopes
     * @throws JwtAuthenticationException When scopes are insufficient
     */
    public function validateScopes(Plain $token, array $requiredScopes): void;
}
