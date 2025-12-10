<?php

declare(strict_types=1);

namespace MethorZ\JwtAuthMiddleware\Exception;

/**
 * Thrown when token lacks required scopes/permissions
 */
final class InsufficientScopeException extends JwtAuthenticationException
{
    /**
     * @param array<string> $requiredScopes
     * @param array<string> $providedScopes
     */
    public static function missingScopes(array $requiredScopes, array $providedScopes): self
    {
        return new self(
            sprintf(
                'Insufficient permissions. Required: [%s], Provided: [%s]',
                implode(', ', $requiredScopes),
                implode(', ', $providedScopes),
            ),
        );
    }
}
