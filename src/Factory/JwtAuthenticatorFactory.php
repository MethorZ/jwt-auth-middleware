<?php

declare(strict_types=1);

namespace MethorZ\JwtAuthMiddleware\Factory;

use MethorZ\JwtAuthMiddleware\Contract\JwtAuthenticatorInterface;
use MethorZ\JwtAuthMiddleware\Contract\JwtValidatorInterface;
use MethorZ\JwtAuthMiddleware\Contract\TokenExtractorInterface;
use MethorZ\JwtAuthMiddleware\Service\JwtAuthenticator;
use Psr\Container\ContainerInterface;

/**
 * Factory for JwtAuthenticator
 */
final readonly class JwtAuthenticatorFactory
{
    public function __invoke(ContainerInterface $container): JwtAuthenticatorInterface
    {
        return new JwtAuthenticator(
            tokenExtractor: $container->get(TokenExtractorInterface::class),
            jwtValidator: $container->get(JwtValidatorInterface::class),
        );
    }
}
