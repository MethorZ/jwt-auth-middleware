<?php

declare(strict_types=1);

namespace MethorZ\JwtAuthMiddleware\Contract;

use Lcobucci\JWT\Token\Plain;

/**
 * Loads user entity from token claims
 *
 * Optional interface - inject implementation to enable user loading
 */
interface UserLoaderInterface
{
    /**
     * Load user entity from validated token
     *
     * @return object|null User entity or null if not found
     */
    public function loadUser(Plain $token): ?object;
}
