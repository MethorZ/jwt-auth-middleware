<?php

declare(strict_types=1);

namespace MethorZ\JwtAuthMiddleware\Factory;

use DateTimeZone;
use InvalidArgumentException;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Signer\Hmac\Sha256 as HS256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256 as RS256;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;
use MethorZ\JwtAuthMiddleware\Contract\JwtValidatorInterface;
use MethorZ\JwtAuthMiddleware\Service\DisabledJwtValidator;
use MethorZ\JwtAuthMiddleware\Service\JwtValidator;
use Psr\Container\ContainerInterface;

use function file_get_contents;
use function sprintf;

/**
 * Factory for JwtValidator with zero-config support
 *
 * When JWT_SECRET (or jwt_auth.secret) is not configured:
 * - Returns a DisabledJwtValidator that skips validation
 * - Public routes work normally
 * - Protected routes (auth.required = true) will fail when token is needed
 *
 * This allows the application to start and serve public routes without
 * JWT configuration, while still requiring proper setup for protected routes.
 */
final readonly class JwtValidatorFactory
{
    /**
     * Get configuration (zero-config: from env vars or container)
     *
     * @return array<string, mixed>
     */
    private function getConfig(ContainerInterface $container): array
    {
        $containerConfig = $container->has('config') ? $container->get('config') : [];
        $jwtConfig = $containerConfig['jwt_auth'] ?? [];

        // Zero-config defaults from environment variables
        /** @var array<string, mixed> $jwtConfigArray */
        $jwtConfigArray = is_array($jwtConfig) ? $jwtConfig : [];

        return [
            'enabled' => $jwtConfigArray['enabled'] ?? true, // Can explicitly disable
            'algorithm' => $jwtConfigArray['algorithm'] ?? $_ENV['JWT_ALGORITHM'] ?? 'HS256',
            'secret' => $jwtConfigArray['secret'] ?? $_ENV['JWT_SECRET'] ?? null,
            'public_key_path' => $jwtConfigArray['public_key_path'] ?? $_ENV['JWT_PUBLIC_KEY_PATH'] ?? null,
            'issuer' => $jwtConfigArray['issuer'] ?? $_ENV['JWT_ISSUER'] ?? null,
            'audience' => $jwtConfigArray['audience'] ?? $_ENV['JWT_AUDIENCE'] ?? null,
        ];
    }

    /**
     * Check if JWT authentication is properly configured
     *
     * @param array<string, mixed> $config
     */
    private function isConfigured(array $config): bool
    {
        // Check if explicitly disabled
        if ($config['enabled'] === false) {
            return false;
        }

        $algorithm = $config['algorithm'] ?? 'HS256';

        // Check if required credentials are present
        return match ($algorithm) {
            'HS256' => ! empty($config['secret']),
            'RS256' => ! empty($config['public_key_path']),
            default => false,
        };
    }

    /**
     * Create signature validation constraint based on algorithm
     *
     * @param array<string, mixed> $config
     */
    private function createSignatureConstraint(array $config): SignedWith
    {
        $algorithm = $config['algorithm'] ?? 'HS256';

        return match ($algorithm) {
            'HS256' => $this->createHS256Constraint($config),
            'RS256' => $this->createRS256Constraint($config),
            default => throw new InvalidArgumentException(
                sprintf('Unsupported JWT algorithm: %s. Supported: HS256, RS256', $algorithm),
            ),
        };
    }

    /**
     * Create HS256 (symmetric) signature constraint
     *
     * @param array<string, mixed> $config
     */
    private function createHS256Constraint(array $config): SignedWith
    {
        $secret = $config['secret'];

        if ($secret === null || $secret === '') {
            throw new InvalidArgumentException(
                'JWT_SECRET environment variable or jwt_auth.secret config is required for HS256 algorithm',
            );
        }

        return new SignedWith(new HS256(), InMemory::plainText($secret));
    }

    /**
     * Create RS256 (asymmetric) signature constraint
     *
     * @param array<string, mixed> $config
     */
    private function createRS256Constraint(array $config): SignedWith
    {
        $publicKeyPath = $config['public_key_path'];

        if ($publicKeyPath === null || $publicKeyPath === '') {
            throw new InvalidArgumentException(
                'JWT_PUBLIC_KEY_PATH environment variable or jwt_auth.public_key_path config is required for RS256',
            );
        }

        $publicKey = file_get_contents($publicKeyPath);

        if ($publicKey === false) {
            throw new InvalidArgumentException(
                sprintf('Cannot read public key from path: %s', $publicKeyPath),
            );
        }

        return new SignedWith(new RS256(), InMemory::plainText($publicKey));
    }

    public function __invoke(ContainerInterface $container): JwtValidatorInterface
    {
        $config = $this->getConfig($container);

        // Return disabled validator if not configured
        // This allows the app to start and serve public routes
        if (! $this->isConfigured($config)) {
            return new DisabledJwtValidator();
        }

        $constraints = [
            // Always validate expiration and not-before
            new StrictValidAt(new SystemClock(new DateTimeZone('UTC'))),

            // Signature validation
            $this->createSignatureConstraint($config),
        ];

        // Optional: Validate issuer
        if (isset($config['issuer']) && $config['issuer'] !== '') {
            $constraints[] = new IssuedBy($config['issuer']);
        }

        // Optional: Validate audience
        if (isset($config['audience']) && $config['audience'] !== '') {
            $constraints[] = new PermittedFor($config['audience']);
        }

        return new JwtValidator($constraints);
    }
}
