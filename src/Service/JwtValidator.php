<?php

declare(strict_types=1);

namespace MethorZ\JwtAuthMiddleware\Service;

use DateTimeImmutable;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\Constraint;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use Lcobucci\JWT\Validation\Validator;
use MethorZ\JwtAuthMiddleware\Contract\JwtValidatorInterface;
use MethorZ\JwtAuthMiddleware\Exception\ExpiredTokenException;
use MethorZ\JwtAuthMiddleware\Exception\InvalidSignatureException;
use MethorZ\JwtAuthMiddleware\Exception\InvalidTokenException;

/**
 * Validates JWT tokens against configured constraints
 */
final readonly class JwtValidator implements JwtValidatorInterface
{
    /**
     * @param array<Constraint> $constraints
     */
    public function __construct(
        private array $constraints,
    ) {
    }

    public function validate(Plain $token): void
    {
        // If no constraints, nothing to validate
        if ($this->constraints === []) {
            return;
        }

        $validator = new Validator();

        try {
            $validator->assert($token, ...$this->constraints);
        } catch (RequiredConstraintsViolated $e) {
            $this->handleViolation($token, $e);
        }
    }

    /**
     * Handle constraint violation with specific exception
     */
    private function handleViolation(
        Plain $token,
        RequiredConstraintsViolated $exception,
    ): never {
        $message = $exception->getMessage();

        // Check if it's a signature issue (check this first as it's most specific)
        if (str_contains($message, 'signature') || str_contains($message, 'mismatch')) {
            throw InvalidSignatureException::verificationFailed();
        }

        // Check if it's an expiration issue by examining the token's exp claim
        $expiredAt = $token->claims()->get('exp');
        if ($expiredAt instanceof DateTimeImmutable) {
            $now = new DateTimeImmutable();
            if ($expiredAt < $now) {
                throw ExpiredTokenException::atTime($expiredAt);
            }
        }

        // Check message for time-related validation
        if (
            str_contains($message, 'expired') ||
            str_contains($message, 'not yet valid') ||
            str_contains($message, 'before') ||
            str_contains($message, 'after')
        ) {
            if ($expiredAt instanceof DateTimeImmutable) {
                throw ExpiredTokenException::atTime($expiredAt);
            }
        }

        // Generic validation failure
        throw InvalidTokenException::malformed($exception);
    }
}
