<?php

declare(strict_types=1);

namespace MethorZ\JwtAuthMiddleware\Factory;

use Lcobucci\JWT\Signer\Hmac\Sha256 as HS256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256 as RS256;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;
use Lcobucci\Clock\SystemClock;
use MethorZ\JwtAuthMiddleware\Contract\JwtValidatorInterface;
use MethorZ\JwtAuthMiddleware\Service\JwtValidator;
use Psr\Container\ContainerInterface;

use function file_get_contents;

/**
 * Factory for JwtValidator with zero-config support
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
        return [
            'algorithm' => $jwtConfig['algorithm'] ?? $_ENV['JWT_ALGORITHM'] ?? 'HS256',
            'secret' => $jwtConfig['secret'] ?? $_ENV['JWT_SECRET'] ?? null,
            'public_key_path' => $jwtConfig['public_key_path'] ?? $_ENV['JWT_PUBLIC_KEY_PATH'] ?? null,
            'issuer' => $jwtConfig['issuer'] ?? $_ENV['JWT_ISSUER'] ?? null,
            'audience' => $jwtConfig['audience'] ?? $_ENV['JWT_AUDIENCE'] ?? null,
        ];
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
            default => throw new \InvalidArgumentException(
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
            throw new \InvalidArgumentException(
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
            throw new \InvalidArgumentException(
                'JWT_PUBLIC_KEY_PATH environment variable or jwt_auth.public_key_path config is required for RS256',
            );
        }

        $publicKey = file_get_contents($publicKeyPath);

        if ($publicKey === false) {
            throw new \InvalidArgumentException(
                sprintf('Cannot read public key from path: %s', $publicKeyPath),
            );
        }

        return new SignedWith(new RS256(), InMemory::plainText($publicKey));
    }

    public function __invoke(ContainerInterface $container): JwtValidatorInterface
    {
        $config = $this->getConfig($container);

        $constraints = [
            // Always validate expiration and not-before
            new StrictValidAt(new SystemClock(new \DateTimeZone('UTC'))),

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
