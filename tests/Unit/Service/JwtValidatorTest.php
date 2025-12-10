<?php

declare(strict_types=1);

namespace MethorZ\JwtAuthMiddleware\Tests\Unit\Service;

use DateTimeImmutable;
use DateTimeZone;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;
use MethorZ\JwtAuthMiddleware\Exception\ExpiredTokenException;
use MethorZ\JwtAuthMiddleware\Exception\InvalidSignatureException;
use MethorZ\JwtAuthMiddleware\Service\JwtValidator;
use PHPUnit\Framework\TestCase;

final class JwtValidatorTest extends TestCase
{
    private Configuration $jwtConfig;

    public function testValidatePassesWithValidToken(): void
    {
        $now = new DateTimeImmutable();
        $token = $this->jwtConfig->builder()
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now)
            ->expiresAt($now->modify('+1 hour'))
            ->relatedTo('user123')
            ->getToken($this->jwtConfig->signer(), $this->jwtConfig->signingKey());

        $validator = new JwtValidator([
            new StrictValidAt(new SystemClock(new DateTimeZone('UTC'))),
            new SignedWith($this->jwtConfig->signer(), $this->jwtConfig->signingKey()),
        ]);

        $validator->validate($token);

        $this->assertTrue(true); // No exception thrown
    }

    public function testValidateThrowsOnExpiredToken(): void
    {
        $pastTime = new DateTimeImmutable('-2 hours');
        $token = $this->jwtConfig->builder()
            ->issuedAt($pastTime)
            ->canOnlyBeUsedAfter($pastTime)
            ->expiresAt(new DateTimeImmutable('-1 hour'))
            ->relatedTo('user123')
            ->getToken($this->jwtConfig->signer(), $this->jwtConfig->signingKey());

        $validator = new JwtValidator([
            new StrictValidAt(new SystemClock(new DateTimeZone('UTC'))),
        ]);

        $this->expectException(ExpiredTokenException::class);
        $this->expectExceptionMessage('expired');

        $validator->validate($token);
    }

    public function testValidateThrowsOnInvalidSignature(): void
    {
        $now = new DateTimeImmutable();
        $token = $this->jwtConfig->builder()
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now)
            ->expiresAt($now->modify('+1 hour'))
            ->relatedTo('user123')
            ->getToken($this->jwtConfig->signer(), $this->jwtConfig->signingKey());

        // Validator with wrong key
        $wrongKey = InMemory::plainText('wrong-secret-key-different-from-signing-key');
        $validator = new JwtValidator([
            new SignedWith(new Sha256(), $wrongKey),
        ]);

        $this->expectException(InvalidSignatureException::class);
        $this->expectExceptionMessage('signature verification failed');

        $validator->validate($token);
    }

    protected function setUp(): void
    {
        $this->jwtConfig = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText('test-secret-key-minimum-256-bits-required-for-hs256'),
        );
    }
}
