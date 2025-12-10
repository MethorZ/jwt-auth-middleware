# Contributing to JWT Auth Middleware

Thank you for considering contributing to this project!

## Code of Conduct

Be respectful, inclusive, and collaborative.

## Getting Started

### Prerequisites

- PHP 8.2 or higher
- Composer
- Git

### Setup

```bash
# Clone repository
git clone https://github.com/methorz/jwt-auth-middleware.git
cd jwt-auth-middleware

# Install dependencies
composer install

# Run tests
composer test

# Check code style
composer cs-check

# Run static analysis
composer analyze
```

## Development Workflow

### 1. Create a Branch

```bash
git checkout -b feature/your-feature-name
```

### 2. Make Changes

- Follow existing code style (PSR-12 + Slevomat)
- Add tests for new functionality
- Update documentation as needed

### 3. Run Quality Checks

```bash
# Auto-fix code style
composer cs-fix

# Run all checks
composer check
```

### 4. Commit

```bash
git add .
git commit -m "feat: Add your feature description"
```

**Commit Message Format:**
- `feat:` New feature
- `fix:` Bug fix
- `docs:` Documentation only
- `style:` Code style changes
- `refactor:` Code refactoring
- `test:` Adding tests
- `chore:` Maintenance tasks

### 5. Push and Create PR

```bash
git push origin feature/your-feature-name
```

Then create a Pull Request on GitHub.

## Code Style

This project follows:
- PSR-12 coding standard
- Slevomat Coding Standard
- PHPStan level 9

**Auto-fix style issues:**

```bash
composer cs-fix
```

## Testing

### Writing Tests

- Place unit tests in `tests/Unit/`
- Test class name: `{ClassName}Test`
- Test method name: `test{MethodName}{ExpectedBehavior}`

**Example:**

```php
public function testValidateSucceedsWithValidToken(): void
{
    // Arrange
    $token = $this->createValidToken();

    // Act
    $this->validator->validate($token);

    // Assert
    $this->assertTrue(true); // No exception thrown
}
```

### Running Tests

```bash
# All tests
composer test

# With coverage
composer test-coverage

# Specific test
vendor/bin/phpunit tests/Unit/Service/JwtAuthenticatorTest.php
```

## Static Analysis

```bash
composer analyze
```

Fix type hints and PHPDoc to resolve issues.

## Pull Request Guidelines

### Before Submitting

- [ ] Tests pass (`composer test`)
- [ ] Code style passes (`composer cs-check`)
- [ ] Static analysis passes (`composer analyze`)
- [ ] Documentation updated
- [ ] CHANGELOG.md updated (if applicable)

### PR Description

Include:
1. **What** - What does this PR do?
2. **Why** - Why is this change needed?
3. **How** - How does it work?
4. **Testing** - How was it tested?

### Review Process

1. Automated CI checks must pass
2. At least one maintainer approval required
3. No unresolved conversations

## Reporting Issues

### Bug Reports

Include:
- PHP version
- Package version
- Steps to reproduce
- Expected behavior
- Actual behavior
- Stack trace (if applicable)

### Feature Requests

Include:
- Use case
- Proposed API
- Alternatives considered
- Willingness to implement

## Questions?

Open a GitHub Discussion or issue.

## License

By contributing, you agree that your contributions will be licensed under the MIT License.

