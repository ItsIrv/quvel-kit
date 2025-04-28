# Testing

## Overview

QuVel Kit implements a comprehensive testing strategy for the Laravel backend using PHPUnit. This guide covers the testing architecture, test types, and best practices for maintaining a robust test suite.

## Testing Architecture

The testing architecture is organized into several test suites:

- **Unit Tests**: Test individual components in isolation
- **Feature Tests**: Test complete features and API endpoints
- **Module Tests**: Test module-specific functionality
- **Integration Tests**: Test interactions between components

## Test Directory Structure

```text
backend/
├── tests/                  # Core application tests
│   ├── Feature/            # Feature tests
│   ├── Unit/               # Unit tests
│   └── TestCase.php        # Base test case
└── Modules/                # Module tests
    └── YourModule/
        └── tests/
            ├── Feature/    # Module feature tests
            └── Unit/       # Module unit tests
```

## Running Tests

### Running All Tests

```bash
php artisan test
```

### Running Tests in Parallel

```bash
php artisan test -p
```

### Running Specific Test Suites

```bash
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
php artisan test --testsuite=Modules
```

### Running Tests with Specific Groups

```bash
php artisan test --group=auth-module
php artisan test --group=tenant-module
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

## Writing Tests

### Base Test Case

All tests extend the base `TestCase` class:

```php
<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase;
    
    // Common setup and helper methods
}
```

### Unit Tests

Unit tests focus on testing individual components in isolation:

```php
<?php

namespace Tests\Unit;

use App\Services\CalculationService;
use Tests\TestCase;

class CalculationServiceTest extends TestCase
{
    public function test_can_calculate_total()
    {
        $service = new CalculationService();
        
        $result = $service->calculateTotal([10, 20, 30]);
        
        $this->assertEquals(60, $result);
    }
}
```

### Feature Tests

Feature tests focus on testing complete features and API endpoints:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_can_get_user_profile()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->getJson('/api/profile');
        
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]);
    }
}
```

### Module Tests

Module tests focus on testing module-specific functionality:

```php
<?php

namespace Modules\Auth\Tests\Feature;

use Modules\Auth\Models\User;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'user' => [
                    'id',
                    'name',
                    'email',
                ],
            ]);
    }
}
```

## Test Factories

Use factories to create test data:

```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;
    
    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'remember_token' => Str::random(10),
        ];
    }
    
    public function admin()
    {
        return $this->state(function (array $attributes) {
            return [
                'role' => 'admin',
            ];
        });
    }
}
```

## Test Database

Tests use an in-memory SQLite database by default. Configure the test database in `phpunit.xml`:

```xml
<php>
    <env name="APP_ENV" value="testing"/>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
</php>
```

## Code Coverage

Generate code coverage reports:

```bash
php artisan test -p --coverage-html=storage/debug/coverage
```

Access coverage reports at:

```
https://coverage-api.quvel.127.0.0.1.nip.io
```

## Mocking

Use mocks to isolate components during testing:

```php
public function test_service_calls_repository()
{
    $repositoryMock = $this->mock(UserRepository::class);
    $repositoryMock->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn(new User(['id' => 1, 'name' => 'Test User']));
    
    $service = app(UserService::class);
    $user = $service->getUserById(1);
    
    $this->assertEquals('Test User', $user->name);
}
```

## Testing Multi-Tenancy

Test tenant-specific functionality:

```php
public function test_tenant_specific_feature()
{
    $tenant = Tenant::factory()->create();
    
    $this->actingAs($user)
        ->withTenant($tenant)
        ->getJson('/api/tenant/resources')
        ->assertStatus(200);
}
```

## Best Practices

1. **Test Coverage**: Aim for high test coverage, especially for critical components
2. **Isolated Tests**: Keep tests isolated and avoid dependencies between tests
3. **Fast Tests**: Optimize tests for speed to maintain a quick feedback loop
4. **Readable Tests**: Write clear, readable tests with descriptive names
5. **Test Data**: Use factories to create test data
6. **Assertions**: Use specific assertions rather than generic ones
7. **Clean Up**: Clean up after tests to avoid affecting other tests

## Continuous Integration

QuVel Kit integrates with GitHub Actions for continuous integration:

```yaml
name: Tests

on:
  push:
    branches: [ main ]
  pull_request:
    branches: [ main ]

jobs:
  tests:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.3'
        extensions: mbstring, dom, fileinfo, mysql
        coverage: xdebug
    
    - name: Install Composer dependencies
      run: composer install --prefer-dist --no-progress
    
    - name: Run tests
      run: php artisan test -p --coverage-clover=coverage.xml
    
    - name: Upload coverage to Codecov
      uses: codecov/codecov-action@v1
      with:
        file: ./coverage.xml
```

---

[← Back to Backend Documentation](./README.md)
