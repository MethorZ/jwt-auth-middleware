<?php

declare(strict_types=1);

namespace MethorZ\JwtAuthMiddleware\Exception;

use RuntimeException;

/**
 * Base exception for all JWT authentication failures
 */
class JwtAuthenticationException extends RuntimeException
{
    /**
     * Create exception with default message
     */
    public static function create(string $message = 'JWT authentication failed'): self
    {
        return new self($message);
    }
}
