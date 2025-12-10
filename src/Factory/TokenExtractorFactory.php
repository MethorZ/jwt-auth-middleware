<?php

declare(strict_types=1);

namespace MethorZ\JwtAuthMiddleware\Factory;

use MethorZ\JwtAuthMiddleware\Contract\TokenExtractorInterface;
use MethorZ\JwtAuthMiddleware\Service\TokenExtractor;
use Psr\Container\ContainerInterface;

/**
 * Factory for TokenExtractor with zero-config support
 */
final readonly class TokenExtractorFactory
{
    /**
     * Get configuration (zero-config: from env vars or container)
     *
     * @return array<string, string>
     */
    private function getConfig(ContainerInterface $container): array
    {
        $containerConfig = $container->has('config') ? $container->get('config') : [];
        $jwtConfig = $containerConfig['jwt_auth'] ?? [];

        return [
            'header_name' => $jwtConfig['header_name'] ?? $_ENV['JWT_HEADER_NAME'] ?? 'Authorization',
            'header_prefix' => $jwtConfig['header_prefix'] ?? $_ENV['JWT_HEADER_PREFIX'] ?? 'Bearer',
        ];
    }

    public function __invoke(ContainerInterface $container): TokenExtractorInterface
    {
        $config = $this->getConfig($container);

        return new TokenExtractor(
            headerName: $config['header_name'],
            headerPrefix: $config['header_prefix'],
        );
    }
}
