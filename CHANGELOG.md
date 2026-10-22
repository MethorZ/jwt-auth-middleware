# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.2.1] - 2026-10-22

### Fixed
- Requests that match no route no longer fail with "Call to a member function getOptions() on false". `RouteResult::getMatchedRoute()` returns `false` when routing fails; the middleware now passes such requests through so the application can answer 404/405.

## [1.2.0] - 2026-10-22

### Changed
- PHP requirement raised to `~8.4.27 || ~8.5.12` (releases patched for php2shell: CVE-2026-109618, CVE-2026-109619). PHP 8.2 and 8.3 are no longer supported.
- Updated dependencies: lcobucci/jwt ^5.6, lcobucci/clock ^3.6; development: laminas/laminas-diactoros ^3.9, phpunit/phpunit ^13.4, phpstan/phpstan ^2.3, slevomat/coding-standard ^8.31, squizlabs/php_codesniffer ^4.0.1.
- PHPStan analyses against the runtime PHP version (removed the `phpVersion: 80200` pin).

## [1.0.0] - 2025-12-15

### Added
- Initial release
- PSR-15 JWT authentication middleware
- Zero-configuration approach with environment variables
- Support for HS256 (symmetric) and RS256 (asymmetric) algorithms
- Bearer token and custom header extraction
- Standard claims validation (exp, nbf, iss, aud)
- Optional user loader interface
- Optional scope/permission validation
- Route-specific authentication
- Framework-agnostic design
- Comprehensive exception hierarchy
- Full test coverage

