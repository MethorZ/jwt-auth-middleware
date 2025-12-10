<?php

declare(strict_types=1);

namespace MethorZ\JwtAuthMiddleware\Contract;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Extracts JWT token string from HTTP request
 */
interface TokenExtractorInterface
{
    /**
     * Extract token from request
     *
     * @return string|null Token string or null if not found
     */
    public function extract(ServerRequestInterface $request): ?string;
}
