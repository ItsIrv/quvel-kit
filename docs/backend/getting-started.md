# Getting Started with Laravel Backend

## Overview

QuVel Kit's backend is built with Laravel 12, using a modular architecture pattern for maintainable and scalable applications. This guide covers the basics of working with the Laravel backend, including setup, development workflow, and key concepts.

## Backend Architecture

The backend follows a modular approach using Laravel Modules, which provides several benefits:

- **Separation of Concerns** - Each module encapsulates specific functionality
- **Maintainability** - Easier to maintain and update individual modules
- **Reusability** - Modules can be reused across different projects
- **Scalability** - New features can be added as separate modules

## Development Environment

### Accessing the Backend Container

Once you've set up QuVel Kit following the main [Getting Started](../getting-started.md) guide, you can access the Laravel backend container:

```bash
# Open a shell in the Laravel container
docker exec -it quvel-app sh
```

### Local Development Setup

For commands that don't require Docker network access (static analysis, code formatting):

```bash
# Install dependencies locally
cd backend
composer install --dev
```

## Core Development Tasks

### Database Operations

```bash
# Run migrations
php artisan migrate

# Seed the database
php artisan db:seed

# Reset and re-run all migrations
php artisan migrate:fresh --seed

# Create a new migration
php artisan make:migration create_your_table_name
```

### Model & Resource Generation

```bash
# Create a model with migration, factory, and resource controller
php artisan make:model YourModel -mfr

# Create an API resource
php artisan make:resource YourModelResource
```

## Project Structure

The Laravel backend follows a modular structure with the following key directories:

```text
backend/
├── app/              # Core application code
│   ├── Actions/      # Business logic actions
│   ├── Http/         # Controllers, middleware, requests
│   ├── Models/       # Eloquent models
│   └── Services/     # Service classes
├── config/           # Configuration files
├── database/         # Migrations, factories, seeders
├── Modules/          # Laravel Modules
│   ├── Auth/         # Authentication module
│   ├── Tenant/       # Multi-tenancy module
│   └── User/         # User management module
├── routes/           # API and web routes
└── tests/            # Test suites
```

## Module Development

QuVel Kit uses Laravel Modules for modular architecture:

```bash
# List all modules
php artisan module:list

# Create a new module
php artisan module:make YourModuleName

# Enable a module
php artisan module:enable YourModuleName
```

## Testing & Quality Assurance

### Running Tests

```bash
# Run all tests
php artisan test

# Run tests in parallel
php artisan test -p

# With Code Coverage
php artisan test -p --coverage-html=storage/coverage

# Run specific test groups
php artisan test --group=tenant-module

# Run specific test suites
php artisan test --testsuite=Modules
```

### Available Test Groups

- `security` - Security-related tests
- `providers` - Service provider tests
- `actions` - Action class tests
- `models` - Model tests
- `transformers` - Data transformer tests
- `services` - Service class tests
- `frontend` - Frontend integration tests
- `tenant-module` - Multi-tenancy tests
- `auth-module` - Authentication tests

### Code Coverage

```bash
# Generate HTML coverage report
php artisan test -p --coverage-html=storage/debug/coverage
```

Access coverage reports at: <https://coverage-api.quvel.127.0.0.1.nip.io>

## Debugging & Troubleshooting

### Laravel Telescope

Access Laravel Telescope at: <https://api.quvel.127.0.0.1.nip.io/telescope>

Telescope provides insights into:

- Requests & responses
- Database queries
- Cache operations
- Queue jobs
- Scheduled tasks
- Log entries

### Interactive Shell

```bash
# Start Tinker (Laravel's REPL)
php artisan tinker
```

### Viewing Logs

```bash
# View Laravel logs
docker logs -f quvel-app

# Tail Laravel log file
php artisan tail:log
```

## Multi-Tenancy

QuVel Kit supports multi-tenancy out of the box. Each tenant has:

- Isolated database schema
- Custom domain routing
- Separate configuration

```bash
# Create a new tenant
php artisan tenant:create

# List all tenants
php artisan tenant:list
```

[← Back to Backend Documentation](./README.md)
