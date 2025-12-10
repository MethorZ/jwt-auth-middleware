<?php

declare(strict_types=1);

namespace MethorZ\JwtAuthMiddleware\Service;

use MethorZ\JwtAuthMiddleware\Contract\TokenExtractorInterface;
use Psr\Http\Message\ServerRequestInterface;

use function str_starts_with;
use function substr;
use function trim;

/**
 * Extracts JWT token from HTTP request headers
 */
final readonly class TokenExtractor implements TokenExtractorInterface
{
    public function __construct(
        private string $headerName = 'Authorization',
        private string $headerPrefix = 'Bearer',
    ) {
    }

    public function extract(ServerRequestInterface $request): ?string
    {
        $headerValue = $request->getHeaderLine($this->headerName);

        if ($headerValue === '') {
            return null;
        }

        // If no prefix is configured, return the entire header value
        if ($this->headerPrefix === '') {
            return trim($headerValue);
        }

        // Check if header starts with expected prefix
        if (! str_starts_with($headerValue, $this->headerPrefix)) {
            return null;
        }

        // Extract token after prefix
        $token = trim(substr($headerValue, strlen($this->headerPrefix)));

        return $token !== '' ? $token : null;
    }
}
