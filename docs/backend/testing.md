# Testing

## Overview

QuVel Kit includes a testing infrastructure built on PHPUnit 10+ with tenant-aware capabilities. The testing architecture provides a foundation that you can extend and customize for your specific project needs.

## Test Configuration

The default testing configuration in `phpunit.xml` includes:

```xml
<testsuites>
    <testsuite name="Unit">
        <directory>tests/Unit</directory>
    </testsuite>
    <testsuite name="Feature">
        <directory>tests/Feature</directory>
    </testsuite>
    <testsuite name="Modules">
        <directory suffix="Test.php">./Modules/*/Tests/Feature</directory>
        <directory suffix="Test.php">./Modules/*/Tests/Unit</directory>
    </testsuite>
</testsuites>
```

## Test Directory Structure

```text
backend/
├── tests/
│   ├── Feature/            # Feature tests
│   ├── Unit/               # Unit tests
│   │   ├── Actions/        # Action tests
│   │   ├── Http/           # HTTP component tests
│   │   ├── Models/         # Model tests
│   │   ├── Providers/      # Service provider tests
│   │   ├── Services/       # Service tests
│   │   └── Traits/         # Trait tests
│   └── TestCase.php        # Base test case
└── Modules/                # Module-specific code
    └── YourModule/
        └── tests/
            ├── Feature/    # Module feature tests
            └── Unit/       # Module unit tests
```

## Running Tests

Laravel's standard testing commands are available:

```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
php artisan test --testsuite=Modules

# Run tests with specific groups
php artisan test --group=tenant-module
php artisan test --group=auth-module
```

## Base Test Case

QuVel Kit provides a base `TestCase` class with tenant-aware capabilities. This class handles tenant seeding and context setup for your tests. You can extend this functionality as needed for your specific testing requirements.

## Test Database

By default, tests use an in-memory SQLite database for speed. This configuration can be modified in `phpunit.xml` according to your project needs:

```xml
<php>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
</php>
```

## Code Coverage

Code coverage reports are generated during test runs and can be accessed at:

```
https://coverage-api.quvel.127.0.0.1.nip.io
```

## Example Tests

QuVel Kit includes example tests that demonstrate how to test various components:

### Unit Test Example

```php
<?php

namespace Tests\Unit\Models;

use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('user-module')]
class UserTest extends TestCase
{
    public function test_user_model_instantiation(): void
    {
        $user = new User();
        $this->assertInstanceOf(User::class, $user);
    }
}
```

### Feature Test Example

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    public function test_users_can_authenticate(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
        
        $response->assertStatus(200);
    }
}
```

## Multi-Tenancy Testing

The base `TestCase` includes tenant context setup, making it easier to test tenant-scoped functionality. This infrastructure is available for you to use and extend as needed.

---

[← Back to Backend Documentation](./README.md)
