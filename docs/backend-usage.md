# Backend Usage

## Accessing the Laravel Backend

The backend of QuVel Kit is powered by **Laravel** and runs inside a Docker container. Below are the steps to interact with the backend for development, migrations, and debugging.

---

## Laravel Service Overview

- The backend runs inside a **Docker container**.
- It uses **PHP 8+**, **MySQL**, and **Redis**.
- The service starts using:

  ```bash
  php artisan serve --host=0.0.0.0 --port=8000
  ```

- It is exposed on <https://api.quvel.127.0.0.1.nip.io>
- Telescope: <https://api.quvel.127.0.0.1.nip.io/telescope>

---

## Connecting To Docker

### Open a Terminal in the Laravel Container

To run artisan commands inside the backend container:

```bash
docker exec -it quvel-app sh 
```

Once inside, you can run commands as you normally would. Note that most commands work on your local machine. However, some commands require the container, such as migrations.

## Testing

### Tinker

```bash
php artisan tinker
```

### Run Tests

```bash
php artisan test # -p
php artisan test --group=providers
```

The following groups are available:

- security
- providers
- actions
- models
- transformers
- services
- frontend
- tenant-module

---

### Access Coverage Reports

You can access the coverage reports at <https://coverage-api.quvel.127.0.0.1.nip.io>.

### Refresh Coverage Report

```bash
php artisan test --coverage-html=storage/debug/coverage
```

---

## Static Analysis

**PHPStan (Static Analysis)**  

```sh
vendor/bin/phpstan analyse --configuration phpstan.neon
```

**PHP-CS-Fixer (Code Style)**  

```sh
vendor/bin/php-cs-fixer fix --dry-run --diff
```

---

## Running Migrations & Database Commands

```bash
php artisan migrate
php artisan db:seed
```

Exit the container with:

```bash
exit
```

---

## Resetting the Database

If you need to reset the database, run:

```bash
./scripts/reset.sh
```

This will stop all containers, remove volumes, and restart everything fresh.

---

## Storage & Linking

If you encounter issues with file uploads or missing storage links, run:

```bash
php artisan storage:link
```

---

## Debugging Backend Issues

### View Backend Logs

```bash
docker logs -f quvel-app
```

### Restart Laravel Service

```bash
docker restart quvel-app
```

---

## Vite Assets

Laravel assets can currently be updated by running the quvel-asset-builder container.
An automated way will be cooked up in the future.

```bash
docker-compose -f docker/docker-compose.yml run --rm asset-builder
```
