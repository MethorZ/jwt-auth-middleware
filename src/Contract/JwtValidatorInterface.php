<?php

declare(strict_types=1);

namespace MethorZ\JwtAuthMiddleware\Contract;

use Lcobucci\JWT\Token\Plain;
use MethorZ\JwtAuthMiddleware\Exception\JwtAuthenticationException;

/**
 * Validates JWT tokens against configured constraints
 */
interface JwtValidatorInterface
{
    /**
     * Validate a JWT token
     *
     * @throws JwtAuthenticationException When validation fails
     */
    public function validate(Plain $token): void;
}
