<?php

declare(strict_types=1);

namespace MethorZ\JwtAuthMiddleware\Factory;

use MethorZ\JwtAuthMiddleware\Contract\JwtAuthenticatorInterface;
use MethorZ\JwtAuthMiddleware\Contract\UserLoaderInterface;
use MethorZ\JwtAuthMiddleware\Middleware\JwtAuthenticationMiddleware;
use Psr\Container\ContainerInterface;

/**
 * Factory for JwtAuthenticationMiddleware
 */
final readonly class JwtAuthenticationMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): JwtAuthenticationMiddleware
    {
        $authenticator = $container->get(JwtAuthenticatorInterface::class);

        // Optional: User loader (only if configured)
        $userLoader = null;
        if ($container->has(UserLoaderInterface::class)) {
            $userLoader = $container->get(UserLoaderInterface::class);
        }

        return new JwtAuthenticationMiddleware(
            authenticator: $authenticator,
            userLoader: $userLoader,
        );
    }
}
