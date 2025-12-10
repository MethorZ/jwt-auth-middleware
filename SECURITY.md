# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |

## Security Best Practices

### JWT Secret Management

**Critical:** Never commit JWT secrets to version control.

```bash
# ✅ GOOD - Use environment variables
JWT_SECRET=your-secure-random-string-at-least-256-bits

# ❌ BAD - Hardcoded in config files
```

**HS256 Requirements:**
- Minimum 256 bits (32 characters)
- Use cryptographically secure random strings
- Rotate regularly

**Generate Secure Keys:**

```bash
# Generate 256-bit secret
openssl rand -base64 32

# For RS256, generate key pair
openssl genrsa -out private.pem 2048
openssl rsa -in private.pem -outform PEM -pubout -out public.pem
```

### Token Expiration

Always set appropriate expiration times:

```php
$token = $config->builder()
    ->expiresAt(new DateTimeImmutable('+1 hour'))  // ✅ Short-lived
    ->getToken($config->signer(), $config->signingKey());
```

**Recommendations:**
- Access tokens: 15-60 minutes
- Refresh tokens: 7-30 days
- Never use tokens without expiration

### HTTPS Only

**Always use HTTPS in production.** JWT tokens in HTTP headers are vulnerable to interception.

```php
// Middleware to enforce HTTPS (example)
if ($_SERVER['HTTPS'] !== 'on' && $_ENV['APP_ENV'] === 'production') {
    throw new \RuntimeException('HTTPS required for JWT authentication');
}
```

### Scope Validation

Use specific scopes, not broad permissions:

```php
// ✅ GOOD - Specific scopes
'scopes' => ['users:read', 'users:write']

// ❌ BAD - Too broad
'scopes' => ['admin', '*']
```

### Token Storage (Client-Side)

**Never store JWT in:**
- localStorage (XSS vulnerable)
- sessionStorage (XSS vulnerable)

**Recommended:**
- HttpOnly cookies (best for web apps)
- Secure memory (mobile apps)

### Rate Limiting

Implement rate limiting for authentication endpoints:

```php
// Example: Limit login attempts
if ($attempts > 5) {
    throw new \Exception('Too many attempts. Try again in 15 minutes.');
}
```

### Input Validation

The middleware validates tokens, but **always validate claims in your handlers:**

```php
$userId = $request->getAttribute('jwt_claims')->get('sub');

// ✅ Validate the user exists and is active
if (!$userRepository->isActive($userId)) {
    throw new \Exception('User account is disabled');
}
```

## Known Security Considerations

### Algorithm Confusion Attack

The middleware enforces specific algorithms (HS256 or RS256) and does not allow algorithm switching. This prevents "none" algorithm attacks.

### Timing Attacks

The `lcobucci/jwt` library uses constant-time comparison for signature verification, mitigating timing attacks.

### Token Replay

Tokens are stateless. To prevent replay:
- Use short expiration times
- Implement token blacklist (optional, requires storage)
- Use `jti` (JWT ID) claim for revocation

## Reporting a Vulnerability

**Do not open public issues for security vulnerabilities.**

Please report security vulnerabilities privately:

1. **Email:** security@methorz.dev
2. **Subject:** [SECURITY] JWT Auth Middleware - Brief Description

**Include:**
- Vulnerability description
- Steps to reproduce
- Potential impact
- Suggested fix (if known)

**Response Time:**
- Initial response: 48 hours
- Fix timeline: Depends on severity
  - Critical: 7 days
  - High: 14 days
  - Medium: 30 days
  - Low: 60 days

## Security Audit

This package has not undergone external security audit. Use at your own risk in production environments.

## License

This security policy is part of the MIT licensed `methorz/jwt-auth-middleware` package.

