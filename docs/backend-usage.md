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

## Running Commands on Local

Make sure to install packages locally if you want to run commands locally. You can run commands on your local machine, so long they do not need to connect to the docker network.
This means most analysis and test commands work on your local machine.

```bash
composer install --dev
```

## Connecting To Docker

### Open a Terminal in the Laravel Container

To run artisan commands inside the backend container:

```bash
docker exec -it quvel-app sh 
```

Once inside, you can run commands as you normally would.

## Testing

### Tinker

```bash
php artisan tinker
```

### Run Tests

```bash
php artisan test # Run tests normally
php artisan test -p # Run test in parallel
php artisan test --group=tenant-module # Run tests in groups
php artisan test --testsuite=Modules # Run Test Suite
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
- auth-module

The following test suites are available:

- Unit
- Feature
- Modules

---

### Access Coverage Reports

You can access the coverage reports at <https://coverage-api.quvel.127.0.0.1.nip.io>.

### Refresh Coverage Report

```bash
php artisan test -p --coverage-html=storage/debug/coverage
```

---

## Static Analysis

**PHPStan (Static Analysis)**  

```sh
vendor/bin/phpstan analyse --configuration phpstan.neon
```

**PHP-CS-Fixer (Code Style)**  

```sh
/vendor/bin/pint --preset psr12
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
