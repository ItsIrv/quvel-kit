# Backend Common Commands

Quick reference for frequently used Laravel commands in QuVel Kit.

## Development Environment

```bash
# Access Laravel container (Docker mode)
docker exec -it quvel-app sh

# Run commands from outside container
docker exec -it quvel-app php artisan [command]

# Local development (traefik-only mode)
cd backend
php artisan [command]
```

## Database Commands

```bash
# Migrations
php artisan migrate                      # Run pending migrations
php artisan migrate:fresh --seed         # Reset database with seeds
php artisan migrate:rollback             # Rollback last migration
php artisan migrate:status               # Check migration status

# Seeding
php artisan db:seed                      # Run all seeders
php artisan db:seed --class=UserSeeder   # Run specific seeder
```

## Module Commands

```bash
# Module Management
php artisan module:list                  # List all modules
php artisan module:make ModuleName       # Create new module
php artisan module:enable ModuleName     # Enable a module
php artisan module:disable ModuleName    # Disable a module

# Module Operations
php artisan module:migrate ModuleName    # Run module migrations
php artisan module:seed ModuleName       # Run module seeders
php artisan module:publish ModuleName    # Publish module assets
```

## Testing Commands

```bash
# Run Tests
php artisan test                         # Run all tests
php artisan test -p                      # Run tests in parallel
php artisan test --group=tenant-module   # Run specific group
php artisan test --testsuite=Modules     # Run specific suite
php artisan test path/to/TestFile.php   # Run specific file

# Coverage
php artisan test -p --coverage-html=storage/debug/coverage
php artisan test --coverage --min=80
```

## Code Quality

```bash
# Laravel Pint (Code Formatting)
./vendor/bin/pint                        # Format all files
./vendor/bin/pint --test                 # Check without fixing
./vendor/bin/pint path/to/file.php       # Format specific file

# PHPStan (Static Analysis)
./vendor/bin/phpstan analyse             # Run analysis
./vendor/bin/phpstan --memory-limit=1G   # Increase memory limit
```

## Artisan Make Commands

```bash
# Models & Resources
php artisan make:model Post -mfr         # Model with migration, factory, resource
php artisan make:resource PostResource   # API resource
php artisan make:request StorePostRequest # Form request

# Module-Specific
php artisan module:make-model Post Blog  # Model in Blog module
php artisan module:make-controller PostController Blog
php artisan module:make-migration create_posts_table Blog
```

## Cache & Optimization

```bash
# Clear Caches
php artisan cache:clear                  # Clear application cache
php artisan config:clear                 # Clear config cache
php artisan route:clear                  # Clear route cache
php artisan view:clear                   # Clear compiled views
php artisan optimize:clear               # Clear all caches

# Optimize
php artisan optimize                     # Cache config, routes, views
php artisan config:cache                 # Cache configuration
php artisan route:cache                  # Cache routes
```

## Tenant Commands

```bash
# Tenant Management
php artisan tenant:list                  # List all tenants
php artisan tenant:dump {id}             # Export tenant data
php artisan tenants:dump                 # Export all tenants
php artisan tenant:cache-clear           # Clear tenant cache
```

## Debugging

```bash
# Laravel Tinker
php artisan tinker                       # Interactive shell

# Logs
php artisan tail:log                     # Tail Laravel logs
docker logs -f quvel-app                 # Container logs

# Queue & Jobs
php artisan queue:work                   # Process queue jobs
php artisan queue:listen                 # Listen for jobs
php artisan queue:retry all              # Retry failed jobs
```

## Maintenance

```bash
# Application Maintenance
php artisan down                         # Put app in maintenance mode
php artisan up                           # Bring app back online

# Storage
php artisan storage:link                 # Create storage symlink

# Keys
php artisan key:generate                 # Generate app key
```

## Useful Aliases

Add these to your shell profile for quicker access:

```bash
# Docker mode aliases
alias dart='docker exec -it quvel-app php artisan'
alias dcomposer='docker exec -it quvel-app composer'
alias dtinker='docker exec -it quvel-app php artisan tinker'

# Local mode aliases  
alias art='php artisan'
alias pint='./vendor/bin/pint'
alias stan='./vendor/bin/phpstan analyse'
```

---

[← Back to Backend Documentation](./README.md)