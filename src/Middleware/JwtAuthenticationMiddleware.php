<?php

declare(strict_types=1);

namespace MethorZ\JwtAuthMiddleware\Middleware;

use Lcobucci\JWT\Token\Plain;
use MethorZ\JwtAuthMiddleware\Contract\JwtAuthenticatorInterface;
use MethorZ\JwtAuthMiddleware\Contract\UserLoaderInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * PSR-15 JWT authentication middleware
 *
 * Authenticates requests via JWT tokens and optionally loads user entities.
 * Works on a route-specific basis - only validates routes with auth configuration.
 */
final readonly class JwtAuthenticationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private JwtAuthenticatorInterface $authenticator,
        private ?UserLoaderInterface $userLoader = null,
    ) {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        // Check if route requires authentication
        $routeOptions = $this->getRouteOptions($request);

        if (! $this->isAuthenticationRequired($routeOptions)) {
            return $handler->handle($request);
        }

        // Authenticate request
        $token = $this->authenticator->authenticate($request);

        // Validate scopes if required
        $requiredScopes = $this->getRequiredScopes($routeOptions);
        if ($requiredScopes !== []) {
            $this->authenticator->validateScopes($token, $requiredScopes);
        }

        // Add token and claims to request attributes
        $request = $this->addTokenAttributes($request, $token);

        // Load user if loader is configured
        if ($this->userLoader !== null) {
            $user = $this->userLoader->loadUser($token);
            if ($user !== null) {
                $request = $request->withAttribute('user', $user);
            }
        }

        return $handler->handle($request);
    }

    /**
     * Get route options from request attributes
     *
     * @return array<string, mixed>
     */
    private function getRouteOptions(ServerRequestInterface $request): array
    {
        // Mezzio stores route options in the RouteResult attribute
        $routeResult = $request->getAttribute('Mezzio\Router\RouteResult');

        if (! is_object($routeResult) || ! method_exists($routeResult, 'getMatchedRoute')) {
            return [];
        }

        // RouteResult::getMatchedRoute() returns false when routing failed
        $matchedRoute = $routeResult->getMatchedRoute();
        if (! is_object($matchedRoute) || ! method_exists($matchedRoute, 'getOptions')) {
            return [];
        }

        $options = $matchedRoute->getOptions();
        $auth = is_array($options) ? ($options['auth'] ?? []) : [];

        return is_array($auth) ? $auth : [];
    }

    /**
     * Check if authentication is required for this route
     *
     * @param array<string, mixed> $routeOptions
     */
    private function isAuthenticationRequired(array $routeOptions): bool
    {
        return ($routeOptions['required'] ?? false) === true;
    }

    /**
     * Get required scopes from route options
     *
     * @param array<string, mixed> $routeOptions
     * @return array<string>
     */
    private function getRequiredScopes(array $routeOptions): array
    {
        $scopes = $routeOptions['scopes'] ?? [];

        if (! is_array($scopes)) {
            return [];
        }

        return $scopes;
    }

    /**
     * Add token and claims to request attributes
     */
    private function addTokenAttributes(ServerRequestInterface $request, Plain $token): ServerRequestInterface
    {
        return $request
            ->withAttribute('jwt_token', $token)
            ->withAttribute('jwt_claims', $token->claims());
    }
}
