# Contributing to EdgeBinder Laminas Component

Thank you for your interest in contributing to the EdgeBinder Laminas Component! This document provides guidelines and information for contributors.

## Development Setup

### Prerequisites

- PHP 8.3+
- Composer
- Docker & Docker Compose
- Git

### Getting Started

1. Fork the repository on GitHub
2. Clone your fork locally:
   ```bash
   git clone https://github.com/your-username/edgebinder-component.git
   cd edgebinder-component
   ```

3. Install dependencies:
   ```bash
   composer install
   ```

4. Start the development environment:
   ```bash
   docker-compose up -d
   ```

5. Run the test suite to ensure everything is working:
   ```bash
   composer test
   ```

## Development Workflow

### Code Quality Standards

This project maintains high code quality standards:

- **PHP 8.3+ features**: Use modern PHP features like readonly classes, enums, and typed properties
- **PSR-12 coding standards**: All code must follow PSR-12
- **PHPStan level 8**: Static analysis at the highest level
- **90%+ test coverage**: Comprehensive test coverage is required
- **Type safety**: Full type declarations for all methods and properties

### Running Quality Checks

```bash
# Run all quality checks
composer ci

# Individual checks
composer cs-check      # Coding standards check
composer cs-fix        # Fix coding standards issues
composer phpstan       # Static analysis
composer test          # Run all tests
composer test-unit     # Unit tests only
composer test-integration  # Integration tests only
composer security-audit    # Security vulnerability check
```

### Testing

We use PHPUnit for testing with three test suites:

- **Unit Tests** (`test/Unit/`): Fast, isolated tests
- **Integration Tests** (`test/Integration/`): Tests with real Weaviate instances
- **Compatibility Tests** (`test/Compatibility/`): Cross-version compatibility tests

#### Writing Tests

- All new features must include comprehensive tests
- Tests should be placed in the appropriate directory based on their type
- Use descriptive test method names that explain what is being tested
- Follow the AAA pattern: Arrange, Act, Assert

#### Test Environment

Integration tests use Docker Compose to provide:
- Multiple Weaviate instances for testing multi-database scenarios
- Consistent test environment across different systems

### Commit Guidelines

- Use clear, descriptive commit messages
- Follow conventional commit format when possible:
  ```
  type(scope): description
  
  feat(factory): add support for custom adapter factories
  fix(config): resolve issue with nested configuration arrays
  docs(readme): update installation instructions
  test(integration): add multi-instance integration tests
  ```

### Pull Request Process

1. Create a feature branch from `main`:
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. Make your changes following the coding standards

3. Add or update tests as needed

4. Run the full test suite:
   ```bash
   composer ci
   ```

5. Commit your changes with clear messages

6. Push to your fork and create a pull request

7. Ensure all CI checks pass

### Code Review

All contributions go through code review:

- PRs require approval from at least one maintainer
- All CI checks must pass
- Code coverage must not decrease
- Documentation must be updated for new features

## Architecture Guidelines

### Component Design

- Follow Laminas/Mezzio conventions
- Use dependency injection throughout
- Implement proper factory patterns
- Support both ServiceManager 3.x and 4.x
- Maintain PSR-11 v1 and v2 compatibility

### Configuration

- Support both flat and nested configuration structures
- Provide sensible defaults
- Include comprehensive validation
- Use typed configuration objects where possible

### Error Handling

- Use specific exception types
- Provide clear error messages
- Include context information in exceptions
- Handle edge cases gracefully

## Documentation

### Code Documentation

- All public methods must have PHPDoc comments
- Include parameter and return type documentation
- Document complex algorithms or business logic
- Use `@throws` tags for exceptions

### User Documentation

- Update relevant documentation files for new features
- Include usage examples
- Document configuration options
- Update the CHANGELOG.md

## Getting Help

- Check existing issues and discussions
- Ask questions in GitHub Discussions
- Join our Discord community
- Review the existing codebase for patterns and examples

## License

By contributing to this project, you agree that your contributions will be licensed under the Apache 2.0 License.
