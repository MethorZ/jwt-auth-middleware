<?php

declare(strict_types=1);

namespace MethorZ\JwtAuthMiddleware\Tests\Unit\Exception;

use DateTimeImmutable;
use MethorZ\JwtAuthMiddleware\Exception\ExpiredTokenException;
use MethorZ\JwtAuthMiddleware\Exception\InsufficientScopeException;
use MethorZ\JwtAuthMiddleware\Exception\InvalidSignatureException;
use MethorZ\JwtAuthMiddleware\Exception\InvalidTokenException;
use MethorZ\JwtAuthMiddleware\Exception\JwtAuthenticationException;
use MethorZ\JwtAuthMiddleware\Exception\MissingTokenException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ExceptionTest extends TestCase
{
    public function testJwtAuthenticationExceptionIsRuntimeException(): void
    {
        $exception = JwtAuthenticationException::create('Test message');

        $this->assertInstanceOf(RuntimeException::class, $exception);
        $this->assertSame('Test message', $exception->getMessage());
    }

    public function testMissingTokenExceptionFromRequest(): void
    {
        $exception = MissingTokenException::fromRequest();

        $this->assertInstanceOf(JwtAuthenticationException::class, $exception);
        $this->assertStringContainsString('No JWT token found', $exception->getMessage());
        $this->assertStringContainsString('Bearer', $exception->getMessage());
    }

    public function testInvalidTokenExceptionMalformed(): void
    {
        $previous = new \Exception('Parse error');
        $exception = InvalidTokenException::malformed($previous);

        $this->assertInstanceOf(JwtAuthenticationException::class, $exception);
        $this->assertStringContainsString('malformed', $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testInvalidTokenExceptionInvalidStructure(): void
    {
        $exception = InvalidTokenException::invalidStructure();

        $this->assertInstanceOf(JwtAuthenticationException::class, $exception);
        $this->assertStringContainsString('invalid structure', $exception->getMessage());
        $this->assertStringContainsString('3 parts', $exception->getMessage());
    }

    public function testExpiredTokenExceptionAtTime(): void
    {
        $expiredAt = new DateTimeImmutable('2024-01-01 12:00:00');
        $exception = ExpiredTokenException::atTime($expiredAt);

        $this->assertInstanceOf(JwtAuthenticationException::class, $exception);
        $this->assertStringContainsString('expired', $exception->getMessage());
        $this->assertStringContainsString('2024-01-01', $exception->getMessage());
    }

    public function testInvalidSignatureExceptionVerificationFailed(): void
    {
        $exception = InvalidSignatureException::verificationFailed();

        $this->assertInstanceOf(JwtAuthenticationException::class, $exception);
        $this->assertStringContainsString('signature verification failed', $exception->getMessage());
        $this->assertStringContainsString('tampered', $exception->getMessage());
    }

    public function testInsufficientScopeExceptionMissingScopes(): void
    {
        $required = ['admin', 'users:write'];
        $provided = ['users:read'];
        $exception = InsufficientScopeException::missingScopes($required, $provided);

        $this->assertInstanceOf(JwtAuthenticationException::class, $exception);
        $this->assertStringContainsString('Insufficient permissions', $exception->getMessage());
        $this->assertStringContainsString('admin', $exception->getMessage());
        $this->assertStringContainsString('users:write', $exception->getMessage());
        $this->assertStringContainsString('users:read', $exception->getMessage());
    }
}
