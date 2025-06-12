# Laravel Octane with Swoole - High Performance Setup

QuVel Kit includes Laravel Octane for high-performance applications. This guide shows how to set up Swoole for maximum performance.

## Installation

### Prerequisites

- PHP 8.1 or higher
- Laravel Octane (already included)

### Install Swoole Extension

#### macOS (using Homebrew)

```bash
# Install Swoole via PECL
pecl install swoole

# Or using Homebrew
brew install swoole
```

#### Ubuntu/Debian

```bash
# Install dependencies
sudo apt-get update
sudo apt-get install php-dev gcc make

# Install Swoole
sudo pecl install swoole
```

#### Docker

```dockerfile
FROM php:8.3-cli

# Install Swoole
RUN pecl install swoole \
    && docker-php-ext-enable swoole
```

### Enable Swoole in PHP

Add to your `php.ini`:

```ini
extension=swoole
```

Verify installation:

```bash
php -m | grep swoole
```

## Configuration

### Octane Configuration

The Octane configuration is already set up in `config/octane.php` with optimized tenant caching:

```php
'tables' => [
    'tenants' => [
        'rows' => env('TENANT_MEMORY_CACHE_MAX_SIZE', 1000),
        'columns' => [
            'tenant'     => 'string:10000',
            'expires_at' => 'int',
        ],
    ],
],
```

### Environment Variables

Add these to your `.env`:

```env
# Octane Configuration
OCTANE_SERVER=swoole
OCTANE_HOST=127.0.0.1
OCTANE_PORT=8000
OCTANE_WORKERS=4

# Tenant Memory Cache
TENANT_MEMORY_CACHE_MAX_SIZE=1000
```

## Running with Octane

### Development

You can use the built-in composer script to start an Octane development server with file watching:

```bash
# Using the composer script (includes file watching, queue worker, logs, and Vite)
composer dev:octane
```

Or run Octane directly:

```bash
php artisan octane:start --server=swoole --host=0.0.0.0 --port=8000
```

#### File Watching

The `--watch` option automatically reloads workers when files change. The watched paths are configured in `config/octane.php`.

### Production

```bash
php artisan octane:start \
    --server=swoole \
    --host=0.0.0.0 \
    --port=8000 \
    --workers=4 \
    --task-workers=6 \
    --max-requests=1000
```

### With Process Management

```bash
# Using Supervisor
php artisan octane:start \
    --server=swoole \
    --host=0.0.0.0 \
    --port=8000 \
    --workers=auto \
    --max-requests=1000 \
    --watch
```

## Performance Features

### 🚀 Tenant Memory Caching

- **80-90% faster** tenant resolution
- **Automatic TTL expiration** (300s default)
- **Size-limited cache** (1000 tenants default)
- **Graceful fallback** for non-Octane environments

### ⚡ Configuration

```env
# Tenant cache TTL (seconds)
TENANT_SSR_RESOLVER_TTL=300

# Memory cache size
TENANT_MEMORY_CACHE_MAX_SIZE=1000
```

### 📊 Expected Performance

- **Request throughput**: 5-10x improvement
- **Memory usage**: Optimized with automatic eviction
- **Tenant resolution**: Near-zero latency for cached tenants

## Deployment

### Production Checklist

- [ ] Swoole extension installed
- [ ] Octane workers configured (`OCTANE_WORKERS`)
- [ ] Memory limits adjusted for tenant cache
- [ ] Process manager (Supervisor) configured
- [ ] Health checks configured (`/up` endpoint)

### Docker Example

```dockerfile
FROM php:8.3-fpm

# Install Swoole
RUN pecl install swoole && docker-php-ext-enable swoole

# Copy application
COPY . /var/www

# Start Octane
CMD ["php", "artisan", "octane:start", "--server=swoole", "--host=0.0.0.0", "--port=8000"]
```

### Supervisor Configuration

```ini
[program:octane]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/application/artisan octane:start --server=swoole --host=127.0.0.1 --port=8000
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/octane.log
```

## Monitoring

### Health Check

```bash
curl http://localhost:8000/up
```

### Memory Usage

```bash
# Check tenant cache stats (if debug enabled)
curl http://localhost:8000/api/tenant/cache/stats
```

## Troubleshooting

### Common Issues

#### Swoole Not Found

```bash
# Check if installed
php -m | grep swoole

# Reinstall if needed
pecl install swoole
```

#### Port Already in Use

```bash
# Find process using port
lsof -ti:8000

# Kill process
kill -9 $(lsof -ti:8000)
```

#### Memory Issues

```bash
# Increase PHP memory limit
ini_set('memory_limit', '512M');

# Or in php.ini
memory_limit = 512M
```

### Fallback Mode

If Swoole is not available, the application automatically falls back to:

- Standard Laravel request handling
- Static array caching for tenants
- All functionality remains available

## Performance Tuning

### Optimal Settings

```env
# For high-traffic applications
OCTANE_WORKERS=8
OCTANE_TASK_WORKERS=12
OCTANE_MAX_REQUESTS=1000

# Tenant caching
TENANT_MEMORY_CACHE_MAX_SIZE=5000
TENANT_SSR_RESOLVER_TTL=600
```

### Memory Optimization

- Monitor cache hit ratios
- Adjust `max_size` based on tenant count
- Use longer TTL for stable tenants
- Enable OPcache for additional performance

## Security Considerations

- Tenant isolation is maintained in memory cache
- Automatic cache invalidation on tenant updates
- No cross-tenant data leakage
- Session validation prevents tenant switching attacks

Ready to experience blazing fast multi-tenant performance! 🚀
