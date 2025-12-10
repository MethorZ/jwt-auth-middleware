<?php

declare(strict_types=1);

namespace MethorZ\JwtAuthMiddleware\Exception;

use Throwable;

/**
 * Thrown when JWT token is malformed or cannot be parsed
 */
final class InvalidTokenException extends JwtAuthenticationException
{
    public static function malformed(Throwable $previous): self
    {
        return new self(
            'JWT token is malformed or cannot be parsed. Ensure token is properly formatted.',
            0,
            $previous,
        );
    }

    public static function invalidStructure(): self
    {
        return new self('JWT token has invalid structure. Expected 3 parts separated by dots.');
    }
}
