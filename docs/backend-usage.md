# Backend Development Guide

## Architecture Overview

QuVel Kit's backend is built with Laravel 12 and follows a modular architecture pattern. The backend provides a robust API for the frontend and supports multi-tenancy out of the box.

| Component | Technology | Version |
|-----------|------------|--------|
| Framework | Laravel | 12.x |
| PHP | PHP | 8.3+ |
| Database | MySQL | 8.0+ |
| Cache | Redis | 6.0+ |
| Queue | Redis | 6.0+ |
| Authentication | Laravel Sanctum | Latest |
| API Documentation | OpenAPI/Swagger | Latest |

## Development Environment

### Accessing the Backend Container

The backend runs in a Docker container. To access it:

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

### Module Development

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

### Test Suites

- `Unit` - Unit tests
- `Feature` - Feature tests
- `Modules` - Module-specific tests

### Code Coverage

```bash
# Generate HTML coverage report
php artisan test -p --coverage-html=storage/debug/coverage
```

Access coverage reports at: <https://coverage-api.quvel.127.0.0.1.nip.io>

### Code Quality Tools

```bash
# Run static analysis
vendor/bin/phpstan analyse --configuration phpstan.neon

# Run code style fixer
vendor/bin/pint --preset psr12
```

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

### Common Issues

#### Storage Link Issues

If file uploads aren't working:

```bash
# Create the symbolic link for storage
php artisan storage:link
```

#### Service Restart

```bash
# Restart the Laravel service
docker restart quvel-app
```

## Asset Compilation

For Laravel Vite assets:

```bash
# Build assets using the asset builder container
docker-compose -f docker/docker-compose.yml run --rm asset-builder
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

---

[← Back to Docs](./README.md)
