<?php

declare(strict_types=1);

namespace MethorZ\JwtAuthMiddleware\Service;

use Lcobucci\JWT\Token\Plain;
use MethorZ\JwtAuthMiddleware\Contract\JwtValidatorInterface;

/**
 * Disabled JWT validator - used when no JWT secret is configured
 *
 * This validator accepts all tokens without validation. It is used
 * when JWT authentication is not configured (no JWT_SECRET set) to
 * allow the application to start and function for public routes.
 *
 * When a route requires authentication (auth.required = true), the
 * JwtAuthenticator will still fail because token parsing requires
 * proper validation. This is intentional - routes that require auth
 * should not work without configuration.
 *
 * @internal Used by JwtValidatorFactory when JWT is not configured
 */
final class DisabledJwtValidator implements JwtValidatorInterface
{
    /**
     * Skip validation - accepts any token
     *
     * This is only reached for routes that DON'T require authentication.
     * Routes requiring auth will fail earlier in JwtAuthenticator.
     *
     * @param Plain $token The token to validate (intentionally unused)
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
     */
    public function validate(Plain $token): void
    {
        // No validation performed - JWT authentication is disabled
    }
}
