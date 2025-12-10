# Zero-Config Approach Explained

## What is "Zero-Config"?

**Zero-Config** means the package works immediately after installation **without requiring any configuration files**. Users can start using it with just environment variables.

## How It Works

### 1. **Environment Variables as Primary Configuration**

```bash
# User only needs to set this
JWT_SECRET=your-secret-key-here

# Package works immediately!
```

### 2. **Smart Fallback Chain**

The package checks for configuration in this order:

```php
// TokenExtractorFactory.php example
$headerName =
    $config['jwt_auth']['header_name']  // 1. Explicit config file (optional)
    ?? $_ENV['JWT_HEADER_NAME']         // 2. Environment variable (zero-config)
    ?? 'Authorization';                 // 3. Sensible default
```

### 3. **Three-Tier Configuration Strategy**

```
┌─────────────────────────────────────────────┐
│ TIER 1: Sensible Defaults (Zero-Config)    │
│  ✅ Works out of the box                    │
│  ✅ Production-ready defaults               │
│  └─ Example: Authorization header, HS256   │
├─────────────────────────────────────────────┤
│ TIER 2: Environment Variables (Optional)   │
│  ✅ No code changes needed                  │
│  ✅ Per-environment configuration           │
│  └─ Example: JWT_SECRET, JWT_ALGORITHM     │
├─────────────────────────────────────────────┤
│ TIER 3: Config File (Advanced)             │
│  ✅ Complex scenarios only                  │
│  ✅ Full control when needed                │
│  └─ Example: config/autoload/jwt-auth.php  │
└─────────────────────────────────────────────┘
```

## Implementation Example

### JwtValidatorFactory.php

```php
private function getConfig(ContainerInterface $container): array
{
    // Try to get from DI container (TIER 3)
    $containerConfig = $container->has('config')
        ? $container->get('config')
        : [];
    $jwtConfig = $containerConfig['jwt_auth'] ?? [];

    // Zero-config defaults from environment variables (TIER 2 & TIER 1)
    return [
        'algorithm' => $jwtConfig['algorithm']         // TIER 3
                    ?? $_ENV['JWT_ALGORITHM']          // TIER 2
                    ?? 'HS256',                        // TIER 1

        'secret'    => $jwtConfig['secret']            // TIER 3
                    ?? $_ENV['JWT_SECRET']             // TIER 2
                    ?? null,                           // TIER 1 (requires user input)

        'issuer'    => $jwtConfig['issuer']            // TIER 3
                    ?? $_ENV['JWT_ISSUER']             // TIER 2
                    ?? null,                           // TIER 1 (optional)
    ];
}
```

## User Experience Comparison

### ❌ **Traditional Approach (Requires Configuration)**

```php
// 1. Install package
composer require methorz/jwt-auth-middleware

// 2. Create config file (REQUIRED)
// config/autoload/jwt-auth.php
return [
    'jwt_auth' => [
        'algorithm' => 'HS256',
        'secret' => 'your-secret',
        'header_name' => 'Authorization',
        'header_prefix' => 'Bearer',
        // ... more boilerplate
    ],
];

// 3. Register services (REQUIRED)
// config/autoload/dependencies.php
return [
    'factories' => [
        JwtAuthenticationMiddleware::class => JwtAuthenticationMiddlewareFactory::class,
        // ... more boilerplate
    ],
];

// 4. Add to pipeline (REQUIRED)
$app->pipe(JwtAuthenticationMiddleware::class);

// 5. Finally works!
```

### ✅ **Zero-Config Approach (Works Immediately)**

```bash
# 1. Install package
composer require methorz/jwt-auth-middleware

# 2. Set environment variable
export JWT_SECRET=your-secret-key-here

# 3. Works immediately! 🎉
```

## Configuration Methods

### Method 1: Pure Zero-Config (Recommended for Most)

```bash
# .env
JWT_SECRET=your-256-bit-secret-key
JWT_ALGORITHM=HS256
JWT_ISSUER=your-app
JWT_AUDIENCE=your-api
```

No PHP configuration files needed!

### Method 2: Config File (Advanced Users)

```php
// config/autoload/jwt-auth.php
return [
    'jwt_auth' => [
        'algorithm' => 'RS256',
        'public_key_path' => '/path/to/public.pem',
        'issuer' => 'my-app',
        'audience' => 'my-api',

        // Advanced: Custom user loader
        'user_loader' => UserLoaderInterface::class,
    ],
];
```

### Method 3: Hybrid (Best of Both)

```php
// config/autoload/jwt-auth.php
return [
    'jwt_auth' => [
        // Use environment variables for secrets
        'secret' => $_ENV['JWT_SECRET'],

        // Use config file for complex logic
        'user_loader' => DatabaseUserLoader::class,
    ],
];
```

## Key Benefits

### 1. **Immediate Usability**
- No configuration ceremony
- Works in 2 steps (install + set secret)
- Perfect for quick prototypes and demos

### 2. **Environment-Agnostic**
- Development: Set `JWT_SECRET` in `.env`
- Staging: Set via deployment config
- Production: Set via environment variables
- No code changes between environments

### 3. **Gradual Complexity**
- Start simple (just environment variables)
- Add config file only when you need advanced features
- Never forced into unnecessary complexity

### 4. **12-Factor App Compliance**
- Configuration in environment (Factor III)
- No secrets in code
- Environment parity

### 5. **Docker/Kubernetes Friendly**
```yaml
# docker-compose.yml
environment:
  - JWT_SECRET=${JWT_SECRET}
  - JWT_ALGORITHM=HS256

# kubernetes secret
env:
  - name: JWT_SECRET
    valueFrom:
      secretKeyRef:
        name: jwt-secret
        key: secret
```

## When to Use Config Files

Use config files ONLY when you need:

1. **Complex Dependencies**
   ```php
   'user_loader' => UserLoaderInterface::class, // Needs DI
   ```

2. **Multiple Algorithms**
   ```php
   'algorithms' => ['HS256', 'RS256'], // Array not possible in env vars
   ```

3. **Dynamic Configuration**
   ```php
   'secret' => $container->get('vault')->getSecret('jwt'),
   ```

4. **Team Conventions**
   ```php
   // If your team prefers all config in files
   ```

## Best Practices

### ✅ DO

```bash
# Use environment variables for:
- Secrets (JWT_SECRET)
- Simple strings (JWT_ISSUER)
- Boolean flags (JWT_STRICT_MODE=true)
- Per-environment settings
```

### ❌ DON'T

```bash
# Don't use environment variables for:
- Complex arrays
- Service class names (use config files)
- Default values (bake into factories)
```

## Comparison with Other Approaches

| Approach | Setup Steps | Flexibility | Beginners |
|----------|------------|-------------|-----------|
| **Zero-Config** | 2 steps | Medium | ✅ Easy |
| **Config Required** | 5+ steps | High | ❌ Hard |
| **Auto-Config** | 3 steps | Low | ⚠️ Medium |

## Summary

**Zero-Config** = **Convention over Configuration**

- Provide smart defaults
- Accept environment variables
- Allow config file override
- Users choose their level of complexity

**Result:** Package works immediately but remains infinitely customizable.

