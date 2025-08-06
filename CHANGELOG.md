# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.2.1] - 2025-08-06

### Fixed
- **CRITICAL**: Fixed EdgeBinder dependency constraint to allow v0.4.0 compatibility
  - Changed constraint from `^0.2` to `>=0.2.0 <1.0.0` to resolve installation conflicts
  - Resolves issue where applications using EdgeBinder ^0.4 couldn't install this component
  - Ensures compatibility with EdgeBinder v0.2.0+ through v0.4.0+ and future versions

## [0.2.0] - 2025-08-06

### Added
- Comprehensive GitHub Actions workflows for CI/CD
  - Lint workflow with php-cs-fixer, phpstan, composer-normalize, and security-audit
  - Test workflow with PHP 8.3/8.4, ServiceManager 3.x/4.x, PSR-11 v1/v2 compatibility matrix
- Docker Compose setup for local development with Weaviate instances
- PHP CS Fixer configuration with PSR-12 and PHP 8.3+ rules
- CONTRIBUTING.md with development guidelines and workflow instructions
- Codecov integration for code coverage reporting

### Changed
- **BREAKING**: Package renamed from `edgebinder/laminas-component` to `edgebinder/edgebinder-laminas-component`
- Updated EdgeBinder dependency constraint from `dev-main` to `^0.2.0` for better version stability
- Converted PHPUnit `@covers` doc-comments to PHP attributes for PHPUnit 12 compatibility
- Removed strict coverage metadata requirements from PHPUnit configuration
- Improved GitHub Actions dependency resolution for ServiceManager/PSR-11 compatibility
- Updated Codecov action to v5 with token authentication for enhanced security

### Fixed
- CI failures due to InMemoryAdapterFactory not being available with wildcard dependency constraint
- PHPUnit deprecation warnings for doc-comment metadata (now uses PHP attributes)
- GitHub Actions dependency resolution issues with ServiceManager and PSR-11 versions
- PHPUnit coverage configuration for proper code coverage generation
- Removed redundant composer validation from tests workflow (handled in lint workflow)

### Removed
- Strict PHPUnit coverage metadata requirements for more flexible testing
- Redundant composer validation step from tests workflow

## [0.1.0] - 2025-08-01

### Added
- Initial release of EdgeBinder Laminas Component
- Self-determining adapter architecture using EdgeBinder AdapterRegistry
- Support for InMemoryAdapter for testing and development
- ConfigProvider for automated Laminas/Mezzio service registration
- EdgeBinderFactory with support for single and multiple instances
- Comprehensive configuration validation and error handling
- Cross-version compatibility with ServiceManager 3.x/4.x and PSR-11 v1/v2
- Complete test suite with unit and integration tests
- Documentation covering installation, configuration, and usage examples
- PHPStan level 8 static analysis compliance
- Configuration template for easy setup

### Security
- Token-based authentication for Codecov integration
- Security audit integration in CI pipeline

[Unreleased]: https://github.com/EdgeBinder/edgebinder-laminas-component/compare/v0.2.1...HEAD
[0.2.1]: https://github.com/EdgeBinder/edgebinder-laminas-component/compare/v0.2.0...v0.2.1
[0.2.0]: https://github.com/EdgeBinder/edgebinder-laminas-component/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/EdgeBinder/edgebinder-laminas-component/releases/tag/v0.1.0
