<?php

declare(strict_types=1);

namespace MethorZ\JwtAuthMiddleware;

use MethorZ\JwtAuthMiddleware\Contract\JwtAuthenticatorInterface;
use MethorZ\JwtAuthMiddleware\Contract\JwtValidatorInterface;
use MethorZ\JwtAuthMiddleware\Contract\TokenExtractorInterface;
use MethorZ\JwtAuthMiddleware\Factory\JwtAuthenticatorFactory;
use MethorZ\JwtAuthMiddleware\Factory\JwtAuthenticationMiddlewareFactory;
use MethorZ\JwtAuthMiddleware\Factory\JwtValidatorFactory;
use MethorZ\JwtAuthMiddleware\Factory\TokenExtractorFactory;
use MethorZ\JwtAuthMiddleware\Middleware\JwtAuthenticationMiddleware;

/**
 * Configuration provider for JWT authentication middleware
 *
 * Registers services and their factories for dependency injection
 */
final readonly class ConfigProvider
{
    /**
     * @return array<string, array<string, string>>
     */
    private function getDependencies(): array
    {
        return [
            'factories' => [
                // Middleware
                JwtAuthenticationMiddleware::class => JwtAuthenticationMiddlewareFactory::class,

                // Services
                JwtAuthenticatorInterface::class => JwtAuthenticatorFactory::class,
                JwtValidatorInterface::class => JwtValidatorFactory::class,
                TokenExtractorInterface::class => TokenExtractorFactory::class,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }
}
