<?php

declare(strict_types=1);

namespace MethorZ\JwtAuthMiddleware\Exception;

/**
 * Thrown when JWT signature verification fails
 */
final class InvalidSignatureException extends JwtAuthenticationException
{
    public static function verificationFailed(): self
    {
        return new self(
            'JWT signature verification failed. Token may have been tampered with or signed with wrong key.',
        );
    }
}
