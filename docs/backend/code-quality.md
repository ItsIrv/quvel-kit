# Code Quality

## Overview

QuVel Kit includes several code quality tools that you can use in your development workflow. This guide describes the available tools and how to use them.

## Available Tools

### PHPStan/Larastan

Larastan (a Laravel-specific extension for PHPStan) is included for static analysis to detect potential errors and type inconsistencies:

```bash
./vendor/bin/phpstan analyse
```

A default configuration is provided in `phpstan.neon` which includes the Larastan extension.

### Laravel Pint

Laravel Pint is included for code formatting based on PSR-12 standards:

```bash
# Check coding standards without making changes
./vendor/bin/pint --test

# Apply code formatting
./vendor/bin/pint --fix
```

A default configuration is provided in `pint.json`.

## Automation Options

### Git Hooks

QuVel Kit includes pre-configured Git hooks that you can install:

```bash
# Install Git hooks
composer install-hooks
```

The included pre-commit hook runs:

1. Laravel Pint
2. PHPStan (Larastan)
3. PHP Unit Tests

### GitHub Actions Workflow

A GitHub Actions workflow template is included for CI/CD:

```yaml
name: Backend CI

on:
  push:
    branches: [main, develop]
    paths: ['backend/**']
  pull_request:
    branches: [main, develop]
    paths: ['backend/**']

jobs:
  backend-tests:
    name: Backend CI Pipeline
    runs-on: ubuntu-latest
    environment: testing
    defaults:
      run:
        working-directory: backend

    steps:
      - name: Checkout Repository
        uses: actions/checkout@v4

      - name: Set up PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: mbstring, pdo, sqlite3
          coverage: xdebug

      - name: Cache Composer Dependencies
        uses: actions/cache@v4
        with:
          path: backend/vendor
          key: vendor-${{ runner.os }}-${{ hashFiles('backend/composer.lock') }}
          restore-keys: vendor-${{ runner.os }}-

      - name: Install Dependencies
        run: composer install --prefer-dist --no-interaction --no-scripts

      - name: Set Up Environment and DB for Parallel Testing
        run: |
          cp .env.example .env
          php artisan key:generate
          echo "DB_CONNECTION=sqlite" >> .env
          echo "DB_DATABASE=database/test.sqlite" >> .env
          mkdir -p database
          touch database/test.sqlite

      - name: Run Security & Dependency Audit (enforced)
        run: composer audit

      - name: Run Static Analysis (PHPStan)
        continue-on-error: true
        run: vendor/bin/phpstan analyse --configuration phpstan.neon

      - name: Run Code Style Check
        continue-on-error: true
        run: vendor/bin/pint --test

      - name: Run Tests in Parallel with Coverage
        env:
          DB_CONNECTION: sqlite
          DB_DATABASE: database/test.sqlite
        run: php artisan test --parallel --coverage-clover=../coverage.xml --testdox

      - name: Upload Coverage to Codecov
        uses: codecov/codecov-action@v4
        with:
          token: ${{ secrets.CODECOV_TOKEN }}
          files: ./coverage.xml
          flags: backend
          name: backend
          fail_ci_if_error: true
```

[← Back to Backend Documentation](./README.md)
