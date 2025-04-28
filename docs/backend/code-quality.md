# Code Quality

## Overview

QuVel Kit maintains high code quality standards through static analysis tools, coding standards, and automated quality checks. This guide covers the tools and practices used to ensure code quality in the Laravel backend.

## Static Analysis Tools

### PHPStan

PHPStan performs static analysis to detect potential errors and type inconsistencies:

```bash
# Run PHPStan analysis
./vendor/bin/phpstan analyse
```

Configuration is defined in `phpstan.neon`:

```yaml
parameters:
    level: 8
    paths:
        - app
        - Modules
    excludePaths:
        - tests/*
    checkMissingIterableValueType: false
```

### Larastan

Larastan extends PHPStan with Laravel-specific rules:

```bash
# Run Larastan analysis
./vendor/bin/phpstan analyse --configuration=larastan.neon
```

### PHP CS Fixer

PHP CS Fixer enforces coding standards:

```bash
# Check coding standards
./vendor/bin/php-cs-fixer fix --dry-run --diff

# Fix coding standards
./vendor/bin/php-cs-fixer fix
```

Configuration is defined in `.php-cs-fixer.php`:

```php
<?php

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'not_operator_with_successor_space' => true,
        'trailing_comma_in_multiline' => true,
        'phpdoc_scalar' => true,
        'unary_operator_spaces' => true,
        'binary_operator_spaces' => true,
        'blank_line_before_statement' => [
            'statements' => ['break', 'continue', 'declare', 'return', 'throw', 'try'],
        ],
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_var_without_name' => true,
        'class_attributes_separation' => [
            'elements' => [
                'method' => 'one',
                'property' => 'one',
            ],
        ],
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
            'keep_multiple_spaces_after_comma' => true,
        ],
        'single_trait_insert_per_statement' => true,
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in([
                __DIR__ . '/app',
                __DIR__ . '/Modules',
                __DIR__ . '/tests',
            ])
            ->name('*.php')
            ->notName('*.blade.php')
            ->ignoreDotFiles(true)
            ->ignoreVCS(true)
    );
```

## Code Style

QuVel Kit follows PSR-12 coding standards with additional Laravel-specific conventions:

### Naming Conventions

- **Classes**: PascalCase (e.g., `UserService`)
- **Interfaces**: PascalCase with Interface suffix (e.g., `UserRepositoryInterface`)
- **Traits**: PascalCase with Trait suffix (e.g., `HasProfileTrait`)
- **Methods**: camelCase (e.g., `getUserById`)
- **Properties**: camelCase (e.g., `$firstName`)
- **Constants**: UPPER_CASE (e.g., `MAX_ATTEMPTS`)

### Documentation

All classes and methods should have proper docblocks:

```php
/**
 * User authentication service.
 */
class UserAuthenticationService
{
    /**
     * Authenticate a user with the provided credentials.
     *
     * @param string $email User email
     * @param string $password User password
     * @return AuthenticationResult Authentication result
     */
    public function authenticate(string $email, string $password): AuthenticationResult
    {
        // Implementation
    }
}
```

### Type Declarations

Use type declarations for method parameters and return types:

```php
public function getUserById(int $id): ?User
{
    return User::find($id);
}
```

## Automated Quality Checks

### Pre-Commit Hooks

Use Git hooks to enforce quality checks before commits:

```bash
# Install Git hooks
composer install-hooks
```

The pre-commit hook runs:

1. PHP CS Fixer
2. PHPStan
3. PHP Unit Tests

### Continuous Integration

Quality checks are run in the CI pipeline:

```yaml
name: Code Quality

on:
  push:
    branches: [ main ]
  pull_request:
    branches: [ main ]

jobs:
  quality:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.3'
        extensions: mbstring, dom, fileinfo
    
    - name: Install Composer dependencies
      run: composer install --prefer-dist --no-progress
    
    - name: Run PHP CS Fixer
      run: ./vendor/bin/php-cs-fixer fix --dry-run --diff
    
    - name: Run PHPStan
      run: ./vendor/bin/phpstan analyse
    
    - name: Run Tests
      run: php artisan test
```

## Code Reviews

Code reviews are an essential part of the quality assurance process:

### Code Review Checklist

1. **Functionality**: Does the code work as expected?
2. **Security**: Are there any security vulnerabilities?
3. **Performance**: Are there any performance issues?
4. **Maintainability**: Is the code maintainable?
5. **Testability**: Is the code testable?
6. **Documentation**: Is the code properly documented?
7. **Coding Standards**: Does the code follow coding standards?

## Best Practices

### SOLID Principles

Follow SOLID principles:

- **Single Responsibility**: Classes should have a single responsibility
- **Open/Closed**: Classes should be open for extension but closed for modification
- **Liskov Substitution**: Subtypes should be substitutable for their base types
- **Interface Segregation**: Clients should not be forced to depend on methods they do not use
- **Dependency Inversion**: Depend on abstractions, not concretions

### Design Patterns

Use appropriate design patterns:

- **Repository Pattern**: For data access
- **Factory Pattern**: For object creation
- **Strategy Pattern**: For interchangeable algorithms
- **Observer Pattern**: For event handling
- **Decorator Pattern**: For adding behavior to objects

### Error Handling

Use proper error handling:

```php
try {
    $result = $this->service->process($data);
} catch (ValidationException $e) {
    return response()->json([
        'message' => 'Validation failed',
        'errors' => $e->errors(),
    ], 422);
} catch (Exception $e) {
    Log::error('Processing error', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    
    return response()->json([
        'message' => 'An error occurred',
    ], 500);
}
```

### Logging

Use structured logging:

```php
Log::info('User authenticated', [
    'user_id' => $user->id,
    'ip' => $request->ip(),
]);
```

## Module-Specific Quality Standards

Each module should maintain its own quality standards:

```
Modules/YourModule/
├── .php-cs-fixer.php      # Module-specific coding standards
├── phpstan.neon           # Module-specific static analysis configuration
└── phpunit.xml            # Module-specific test configuration
```

---

[← Back to Backend Documentation](./README.md)
