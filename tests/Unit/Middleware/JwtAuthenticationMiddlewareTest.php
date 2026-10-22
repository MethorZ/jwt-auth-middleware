<?php

declare(strict_types=1);

namespace MethorZ\JwtAuthMiddleware\Tests\Unit\Middleware;

use DateTimeImmutable;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use MethorZ\JwtAuthMiddleware\Contract\UserLoaderInterface;
use MethorZ\JwtAuthMiddleware\Exception\MissingTokenException;
use MethorZ\JwtAuthMiddleware\Middleware\JwtAuthenticationMiddleware;
use MethorZ\JwtAuthMiddleware\Service\JwtAuthenticator;
use MethorZ\JwtAuthMiddleware\Service\JwtValidator;
use MethorZ\JwtAuthMiddleware\Service\TokenExtractor;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class JwtAuthenticationMiddlewareTest extends TestCase
{
    private Configuration $jwtConfig;
    private JwtAuthenticationMiddleware $middleware;

    public function testProcessSkipsAuthenticationWhenNotRequired(): void
    {
        $request = new ServerRequest();
        $handler = $this->createHandler();

        $response = $this->middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testProcessSkipsAuthenticationWhenNoRouteMatched(): void
    {
        $routeResult = new class {
            public function getMatchedRoute(): false
            {
                return false;
            }
        };
        $request = (new ServerRequest())->withAttribute('Mezzio\Router\RouteResult', $routeResult);
        $handler = $this->createHandler();

        $response = $this->middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testProcessAuthenticatesWhenRequired(): void
    {
        $token = $this->createValidToken();
        $request = $this->createAuthenticatedRequest($token->toString());
        $request = $this->setRouteAuthRequired($request, true);
        $handler = $this->createHandler();

        $response = $this->middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testProcessAddsTokenAttributesToRequest(): void
    {
        $token = $this->createValidToken();
        $request = $this->createAuthenticatedRequest($token->toString());
        $request = $this->setRouteAuthRequired($request, true);

        $capturedRequest = null;
        $handler = $this->createHandler(function (ServerRequestInterface $req) use (&$capturedRequest): void {
            $capturedRequest = $req;
        });

        $this->middleware->process($request, $handler);

        $this->assertNotNull($capturedRequest);
        $this->assertNotNull($capturedRequest->getAttribute('jwt_token'));
        $this->assertNotNull($capturedRequest->getAttribute('jwt_claims'));
        $this->assertSame('user123', $capturedRequest->getAttribute('jwt_claims')->get('sub'));
    }

    public function testProcessThrowsWhenAuthRequiredButTokenMissing(): void
    {
        $request = new ServerRequest();
        $request = $this->setRouteAuthRequired($request, true);
        $handler = $this->createHandler();

        $this->expectException(MissingTokenException::class);

        $this->middleware->process($request, $handler);
    }

    public function testProcessLoadsUserWhenLoaderConfigured(): void
    {
        $token = $this->createValidToken();
        $request = $this->createAuthenticatedRequest($token->toString());
        $request = $this->setRouteAuthRequired($request, true);

        $userLoader = $this->createStub(UserLoaderInterface::class);
        $userLoader->method('loadUser')->willReturn((object) ['id' => 'user123']);

        $tokenExtractor = new TokenExtractor();
        $jwtValidator = new JwtValidator([]);
        $authenticator = new JwtAuthenticator($tokenExtractor, $jwtValidator);
        $middleware = new JwtAuthenticationMiddleware($authenticator, $userLoader);

        $capturedRequest = null;
        $handler = $this->createHandler(function (ServerRequestInterface $req) use (&$capturedRequest): void {
            $capturedRequest = $req;
        });

        $middleware->process($request, $handler);

        $this->assertNotNull($capturedRequest);
        $this->assertNotNull($capturedRequest->getAttribute('user'));
        $this->assertSame('user123', $capturedRequest->getAttribute('user')->id);
    }

    protected function setUp(): void
    {
        $this->jwtConfig = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText('test-secret-key-minimum-256-bits-required-for-hs256'),
        );

        $tokenExtractor = new TokenExtractor();
        $jwtValidator = new JwtValidator([]);
        $authenticator = new JwtAuthenticator($tokenExtractor, $jwtValidator);

        $this->middleware = new JwtAuthenticationMiddleware($authenticator);
    }

    private function createValidToken(): \Lcobucci\JWT\Token\Plain
    {
        $now = new DateTimeImmutable();
        return $this->jwtConfig->builder()
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now)
            ->expiresAt($now->modify('+1 hour'))
            ->relatedTo('user123')
            ->getToken($this->jwtConfig->signer(), $this->jwtConfig->signingKey());
    }

    private function createAuthenticatedRequest(string $tokenString): ServerRequest
    {
        return new ServerRequest(
            [],
            [],
            null,
            null,
            'php://input',
            ['Authorization' => 'Bearer ' . $tokenString],
        );
    }

    private function setRouteAuthRequired(ServerRequest $request, bool $required): ServerRequest
    {
        $routeResult = new class ($required) {
            public function __construct(private bool $required)
            {
            }

            public function getMatchedRoute(): object
            {
                return new class ($this->required) {
                    public function __construct(private bool $required)
                    {
                    }

                    /**
                     * @return array<string, mixed>
                     */
                    public function getOptions(): array
                    {
                        return ['auth' => ['required' => $this->required]];
                    }
                };
            }
        };

        return $request->withAttribute('Mezzio\Router\RouteResult', $routeResult);
    }

    private function createHandler(?callable $callback = null): RequestHandlerInterface
    {
        return new class ($callback) implements RequestHandlerInterface {
            /** @var callable|null */
            private $callback;

            public function __construct(?callable $callback = null)
            {
                $this->callback = $callback;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                if ($this->callback !== null) {
                    ($this->callback)($request);
                }

                return new Response(status: 200);
            }
        };
    }
}
