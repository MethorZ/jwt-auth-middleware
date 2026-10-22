# JWT Authentication Middleware

[![CI](https://github.com/methorz/jwt-auth-middleware/actions/workflows/ci.yml/badge.svg)](https://github.com/methorz/jwt-auth-middleware/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/methorz/jwt-auth-middleware/branch/main/graph/badge.svg)](https://codecov.io/gh/methorz/jwt-auth-middleware)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%20max-brightgreen.svg?style=flat)](https://phpstan.org/)
[![PHP Version](https://img.shields.io/badge/php-~8.4.27%20%7C%7C%20~8.5.12-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

PSR-15 JWT authentication middleware with **zero-config approach**. Just add your JWT secret and you're ready to go!

## Features

- ✅ **Zero Configuration** - Works out of the box with environment variables
- ✅ **PSR-15 Compliant** - Standard middleware interface
- ✅ **Framework Agnostic** - Works with any PSR-15 compatible framework
- ✅ **Flexible Token Extraction** - Bearer token (default) or custom header
- ✅ **Multiple Algorithms** - Supports HS256 (symmetric) and RS256 (asymmetric)
- ✅ **Standard Claims Validation** - Validates exp, nbf, iss, aud automatically
- ✅ **Optional User Loading** - Inject your own user loader
- ✅ **Optional Scope Validation** - Route-level permission checks
- ✅ **Route-Specific Protection** - Opt-in authentication per route

## Installation

```bash
composer require methorz/jwt-auth-middleware
```

## Quick Start

### 1. Set Environment Variable

```bash
# For symmetric key (HS256)
JWT_SECRET=your-secret-key-here

# Or for asymmetric (RS256)
JWT_PUBLIC_KEY_PATH=/path/to/public.pem
JWT_ALGORITHM=RS256
```

### 2. Add to Your Pipeline

**Mezzio:**

```php
// config/pipeline.php
$app->pipe(\MethorZ\JwtAuthMiddleware\Middleware\JwtAuthenticationMiddleware::class);
```

**Slim:**

```php
$app->add(\MethorZ\JwtAuthMiddleware\Middleware\JwtAuthenticationMiddleware::class);
```

### 3. Protect Routes

**Mezzio:**

```php
$app->get('/api/protected', ProtectedHandler::class, 'protected-route')
    ->setOptions(['auth' => ['required' => true]]);
```

**In Your Handler:**

```php
public function handle(ServerRequestInterface $request): ResponseInterface
{
    // Get JWT claims from request
    $claims = $request->getAttribute('jwt_claims');
    $userId = $claims->get('sub'); // User ID from token

    // Optional: Get loaded user (if user loader configured)
    $user = $request->getAttribute('user');

    return new JsonResponse(['user_id' => $userId]);
}
```

That's it! 🎉

## Configuration

### Environment Variables (Zero-Config)

| Variable | Default | Description |
|----------|---------|-------------|
| `JWT_SECRET` | - | Secret key for HS256 (required if using symmetric) |
| `JWT_PUBLIC_KEY_PATH` | - | Path to public key for RS256 (required if using asymmetric) |
| `JWT_ALGORITHM` | `HS256` | Algorithm: `HS256` or `RS256` |
| `JWT_ISSUER` | - | Expected `iss` claim (optional) |
| `JWT_AUDIENCE` | - | Expected `aud` claim (optional) |
| `JWT_HEADER_NAME` | `Authorization` | Header to extract token from |
| `JWT_HEADER_PREFIX` | `Bearer` | Token prefix (e.g., "Bearer ") |

### Advanced Configuration (Optional)

If you need more control, create a configuration file:

```php
// config/autoload/jwt-auth.php
return [
    'jwt_auth' => [
        'algorithm' => 'HS256',
        'secret' => $_ENV['JWT_SECRET'],

        // Validation constraints
        'issuer' => 'your-app',
        'audience' => 'your-api',

        // Token extraction
        'header_name' => 'Authorization',
        'header_prefix' => 'Bearer',

        // Optional: Custom header
        // 'header_name' => 'X-Auth-Token',
        // 'header_prefix' => '',

        // Optional: User loader service
        // 'user_loader' => UserLoaderInterface::class,
    ],
];
```

## Usage Examples

### Protect Specific Routes

```php
// Only require JWT for specific routes
$app->get('/public', PublicHandler::class); // No auth

$app->get('/protected', ProtectedHandler::class)
    ->setOptions(['auth' => ['required' => true]]); // Requires JWT
```

### Scope/Permission Validation

```php
// Require specific scopes
$app->post('/admin/users', AdminHandler::class)
    ->setOptions([
        'auth' => [
            'required' => true,
            'scopes' => ['admin', 'users:write'],
        ],
    ]);
```

In your token, include a `scope` claim:

```json
{
  "sub": "user123",
  "scope": "admin users:write users:read"
}
```

### Custom User Loading

Implement `UserLoaderInterface` to load user from database:

```php
use MethorZ\JwtAuthMiddleware\Contract\UserLoaderInterface;
use Lcobucci\JWT\Token\Plain;

class DatabaseUserLoader implements UserLoaderInterface
{
    public function __construct(private UserRepository $repository) {}

    public function loadUser(Plain $token): ?object
    {
        $userId = $token->claims()->get('sub');
        return $this->repository->findById($userId);
    }
}
```

Register in container:

```php
// ConfigProvider.php
use MethorZ\JwtAuthMiddleware\Contract\UserLoaderInterface;

return [
    'dependencies' => [
        'factories' => [
            UserLoaderInterface::class => DatabaseUserLoaderFactory::class,
        ],
    ],
];
```

Now `$request->getAttribute('user')` contains your user object!

### Generate Tokens (Separate from Middleware)

```php
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;

$config = Configuration::forSymmetricSigner(
    new Sha256(),
    InMemory::plainText($_ENV['JWT_SECRET'])
);

$now = new DateTimeImmutable();
$token = $config->builder()
    ->issuedBy('your-app')
    ->permittedFor('your-api')
    ->identifiedBy('unique-token-id')
    ->issuedAt($now)
    ->expiresAt($now->modify('+1 hour'))
    ->withClaim('sub', 'user123')
    ->withClaim('scope', 'users:read users:write')
    ->getToken($config->signer(), $config->signingKey());

echo $token->toString(); // Send to client
```

## Request Attributes

After successful authentication, these attributes are added to the request:

| Attribute | Type | Description |
|-----------|------|-------------|
| `jwt_token` | `Lcobucci\JWT\Token\Plain` | Full parsed token |
| `jwt_claims` | `Lcobucci\JWT\Token\DataSet` | Token claims |
| `user` | `object|null` | Loaded user (if user loader configured) |

## Exceptions

All exceptions extend `MethorZ\JwtAuthMiddleware\Exception\JwtAuthenticationException`:

| Exception | When Thrown |
|-----------|-------------|
| `MissingTokenException` | No token in request |
| `InvalidTokenException` | Token is malformed |
| `ExpiredTokenException` | Token has expired |
| `InvalidSignatureException` | Signature verification failed |
| `InsufficientScopeException` | Required scope missing |

**Recommendation:** Use with [`methorz/http-problem-details`](https://github.com/methorz/http-problem-details) to automatically format exceptions as RFC 7807 Problem Details responses.

## Testing

```bash
composer test          # Run tests
composer cs-check      # Check code style
composer cs-fix        # Fix code style
composer analyze       # Static analysis
composer check         # All checks
```

## Requirements

- PHP 8.4 (>= 8.4.27) or PHP 8.5 (>= 8.5.12)
- PSR-15 compatible framework
- lcobucci/jwt ^5.6

## License

MIT License. See [LICENSE](LICENSE) for details.

## Contributing

Contributions welcome! Please ensure:
- All tests pass
- Code follows PSR-12
- PHPStan level 9 passes
- Zero-config principle maintained

