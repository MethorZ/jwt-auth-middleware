<?php

declare(strict_types=1);

namespace MethorZ\JwtAuthMiddleware\Tests\Unit\Service;

use DateTimeImmutable;
use Laminas\Diactoros\ServerRequest;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use MethorZ\JwtAuthMiddleware\Exception\InsufficientScopeException;
use MethorZ\JwtAuthMiddleware\Exception\InvalidTokenException;
use MethorZ\JwtAuthMiddleware\Exception\MissingTokenException;
use MethorZ\JwtAuthMiddleware\Service\JwtAuthenticator;
use MethorZ\JwtAuthMiddleware\Service\JwtValidator;
use MethorZ\JwtAuthMiddleware\Service\TokenExtractor;
use PHPUnit\Framework\TestCase;

final class JwtAuthenticatorTest extends TestCase
{
    private Configuration $jwtConfig;
    private JwtAuthenticator $authenticator;

    public function testAuthenticateSucceedsWithValidToken(): void
    {
        $token = $this->createValidToken();
        $request = new ServerRequest(
            [],
            [],
            null,
            null,
            'php://input',
            ['Authorization' => 'Bearer ' . $token->toString()],
        );

        $result = $this->authenticator->authenticate($request);

        $this->assertSame('user123', $result->claims()->get('sub'));
    }

    public function testAuthenticateThrowsWhenTokenMissing(): void
    {
        $request = new ServerRequest();

        $this->expectException(MissingTokenException::class);
        $this->expectExceptionMessage('No JWT token found');

        $this->authenticator->authenticate($request);
    }

    public function testAuthenticateThrowsWhenTokenMalformed(): void
    {
        $request = new ServerRequest(
            [],
            [],
            null,
            null,
            'php://input',
            ['Authorization' => 'Bearer invalid.token'],
        );

        $this->expectException(InvalidTokenException::class);

        $this->authenticator->authenticate($request);
    }

    public function testValidateScopesSucceedsWhenAllScopesPresent(): void
    {
        $token = $this->createTokenWithScopes('users:read users:write admin');
        $request = new ServerRequest(
            [],
            [],
            null,
            null,
            'php://input',
            ['Authorization' => 'Bearer ' . $token->toString()],
        );

        $parsedToken = $this->authenticator->authenticate($request);

        $this->authenticator->validateScopes($parsedToken, ['users:read', 'admin']);

        $this->assertTrue(true); // No exception thrown
    }

    public function testValidateScopesThrowsWhenScopeMissing(): void
    {
        $token = $this->createTokenWithScopes('users:read');
        $request = new ServerRequest(
            [],
            [],
            null,
            null,
            'php://input',
            ['Authorization' => 'Bearer ' . $token->toString()],
        );

        $parsedToken = $this->authenticator->authenticate($request);

        $this->expectException(InsufficientScopeException::class);
        $this->expectExceptionMessage('Insufficient permissions');

        $this->authenticator->validateScopes($parsedToken, ['users:read', 'admin']);
    }

    public function testValidateScopesSkipsWhenNoScopesRequired(): void
    {
        $token = $this->createValidToken();
        $request = new ServerRequest(
            [],
            [],
            null,
            null,
            'php://input',
            ['Authorization' => 'Bearer ' . $token->toString()],
        );

        $parsedToken = $this->authenticator->authenticate($request);

        $this->authenticator->validateScopes($parsedToken, []);

        $this->assertTrue(true); // No exception thrown
    }

    public function testValidateScopesHandlesArrayScopes(): void
    {
        $now = new DateTimeImmutable();
        $token = $this->jwtConfig->builder()
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now)
            ->expiresAt($now->modify('+1 hour'))
            ->relatedTo('user123')
            ->withClaim('scope', ['users:read', 'users:write'])
            ->getToken($this->jwtConfig->signer(), $this->jwtConfig->signingKey());

        $request = new ServerRequest(
            [],
            [],
            null,
            null,
            'php://input',
            ['Authorization' => 'Bearer ' . $token->toString()],
        );

        $parsedToken = $this->authenticator->authenticate($request);

        $this->authenticator->validateScopes($parsedToken, ['users:read']);

        $this->assertTrue(true); // No exception thrown
    }

    protected function setUp(): void
    {
        $this->jwtConfig = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText('test-secret-key-minimum-256-bits-required-for-hs256'),
        );

        $tokenExtractor = new TokenExtractor();
        $jwtValidator = new JwtValidator([]);

        $this->authenticator = new JwtAuthenticator($tokenExtractor, $jwtValidator);
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

    private function createTokenWithScopes(string $scopes): \Lcobucci\JWT\Token\Plain
    {
        $now = new DateTimeImmutable();
        return $this->jwtConfig->builder()
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now)
            ->expiresAt($now->modify('+1 hour'))
            ->relatedTo('user123')
            ->withClaim('scope', $scopes)
            ->getToken($this->jwtConfig->signer(), $this->jwtConfig->signingKey());
    }
}
