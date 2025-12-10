<?php

declare(strict_types=1);

namespace MethorZ\JwtAuthMiddleware\Exception;

use DateTimeImmutable;

/**
 * Thrown when JWT token has expired
 */
final class ExpiredTokenException extends JwtAuthenticationException
{
    public static function atTime(DateTimeImmutable $expiredAt): self
    {
        return new self(
            sprintf(
                'JWT token has expired at %s. Please obtain a new token.',
                $expiredAt->format('Y-m-d H:i:s'),
            ),
        );
    }
}
