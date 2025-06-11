<?php

namespace Modules\Tenant\Tests\Unit\Pipes;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Pipes\BaseConfigurationPipe;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the BaseConfigurationPipe abstract class.
 */
#[CoversClass(BaseConfigurationPipe::class)]
#[Group('tenant-module')]
#[Group('tenant-pipes')]
class BaseConfigurationPipeTest extends TestCase
{
    private TestableBaseConfigurationPipe $pipe;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pipe = new TestableBaseConfigurationPipe();
    }

    public function testDefaultResolveReturnsEmptyArrays(): void
    {
        $tenant = $this->createMock(Tenant::class);
        $tenantConfig = ['some_key' => 'some_value'];

        $result = $this->pipe->resolve($tenant, $tenantConfig);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('values', $result);
        $this->assertArrayHasKey('visibility', $result);
        $this->assertEquals([], $result['values']);
        $this->assertEquals([], $result['visibility']);
    }

    public function testHasValueReturnsTrueForExistingNonEmptyValues(): void
    {
        $tenantConfig = [
            'valid_string' => 'value',
            'valid_number' => 123,
            'valid_bool' => false,
            'valid_array' => ['item'],
        ];

        $this->assertTrue($this->pipe->testHasValue($tenantConfig, 'valid_string'));
        $this->assertTrue($this->pipe->testHasValue($tenantConfig, 'valid_number'));
        $this->assertTrue($this->pipe->testHasValue($tenantConfig, 'valid_bool'));
        $this->assertTrue($this->pipe->testHasValue($tenantConfig, 'valid_array'));
    }

    public function testHasValueReturnsFalseForEmptyOrMissingValues(): void
    {
        $tenantConfig = [
            'empty_string' => '',
            'null_value' => null,
            'zero' => 0,
        ];

        $this->assertFalse($this->pipe->testHasValue($tenantConfig, 'empty_string'));
        $this->assertFalse($this->pipe->testHasValue($tenantConfig, 'null_value'));
        $this->assertFalse($this->pipe->testHasValue($tenantConfig, 'missing_key'));

        // Zero is considered a valid value
        $this->assertTrue($this->pipe->testHasValue($tenantConfig, 'zero'));
    }

    public function testGetValueReturnsExistingValues(): void
    {
        $tenantConfig = [
            'string_key' => 'string_value',
            'number_key' => 42,
            'bool_key' => true,
            'array_key' => ['a', 'b'],
            'null_key' => null,
        ];

        $this->assertEquals('string_value', $this->pipe->testGetValue($tenantConfig, 'string_key'));
        $this->assertEquals(42, $this->pipe->testGetValue($tenantConfig, 'number_key'));
        $this->assertEquals(true, $this->pipe->testGetValue($tenantConfig, 'bool_key'));
        $this->assertEquals(['a', 'b'], $this->pipe->testGetValue($tenantConfig, 'array_key'));
        $this->assertNull($this->pipe->testGetValue($tenantConfig, 'null_key'));
    }

    public function testGetValueReturnsDefaultForMissingKeys(): void
    {
        $tenantConfig = ['existing' => 'value'];

        $this->assertNull($this->pipe->testGetValue($tenantConfig, 'missing'));
        $this->assertEquals('default', $this->pipe->testGetValue($tenantConfig, 'missing', 'default'));
        $this->assertEquals(123, $this->pipe->testGetValue($tenantConfig, 'missing', 123));
        $this->assertEquals(['default'], $this->pipe->testGetValue($tenantConfig, 'missing', ['default']));
    }

    public function testGetValueUsesDefaultForExistingNullValues(): void
    {
        $tenantConfig = ['null_key' => null];

        // PHP's null coalescing operator returns the default when value is null
        $this->assertEquals('default', $this->pipe->testGetValue($tenantConfig, 'null_key', 'default'));
    }
}

/**
 * Testable implementation of BaseConfigurationPipe for unit testing.
 */
class TestableBaseConfigurationPipe extends BaseConfigurationPipe
{
    /**
     * Required by interface but not tested in base class test.
     */
    public function handle(Tenant $tenant, ConfigRepository $config, array $tenantConfig, callable $next): mixed
    {
        return $next([
            'tenant' => $tenant,
            'config' => $config,
            'tenantConfig' => $tenantConfig,
        ]);
    }

    /**
     * Required by interface but not tested in base class test.
     */
    public function handles(): array
    {
        return [];
    }

    /**
     * Required by interface but not tested in base class test.
     */
    public function priority(): int
    {
        return 0;
    }

    /**
     * Expose protected hasValue method for testing.
     */
    public function testHasValue(array $tenantConfig, string $key): bool
    {
        return $this->hasValue($tenantConfig, $key);
    }

    /**
     * Expose protected getValue method for testing.
     */
    public function testGetValue(array $tenantConfig, string $key, mixed $default = null): mixed
    {
        return $this->getValue($tenantConfig, $key, $default);
    }
}
