<?php

declare(strict_types=1);

namespace MethorZ\JwtAuthMiddleware\Exception;

/**
 * Thrown when no JWT token is found in the request
 */
final class MissingTokenException extends JwtAuthenticationException
{
    public static function fromRequest(): self
    {
        return new self('No JWT token found in request. Expected Authorization header with Bearer token.');
    }
}
