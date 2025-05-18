# Code Quality

## Overview

QuVel Kit includes several code quality tools that you can use in your development workflow. This guide describes the available tools and how to use them.

## Available Tools

### PHPStan

PHPStan is included for static analysis to detect potential errors and type inconsistencies:

```bash
# Run PHPStan analysis
./vendor/bin/phpstan analyse
```

A default configuration is provided in `phpstan.neon`:

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

### Laravel Pint

Laravel Pint is included for code formatting based on PSR-12 standards:

```bash
# Check coding standards without making changes
./vendor/bin/pint --test

# Apply code formatting
./vendor/bin/pint --fix
```

## Automation Options

### Git Hooks

QuVel Kit includes pre-configured Git hooks that you can install:

```bash
# Install Git hooks
composer install-hooks
```

The included pre-commit hook runs:

1. Laravel Pint
2. PHPStan
3. PHP Unit Tests

### GitHub Actions Workflow

A GitHub Actions workflow template is included for CI/CD:

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

[← Back to Backend Documentation](./README.md)
