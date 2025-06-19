<?php

namespace Modules\Tenant\Tests\Unit\Builders;

use Modules\Tenant\Builders\TenantTableBuilder;
use Modules\Tenant\Contracts\TableBuilderInterface;
use Modules\Tenant\ValueObjects\TenantTableConfig;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

#[CoversClass(TenantTableBuilder::class)]
#[Group('tenant-module')]
#[Group('tenant-builders')]
class TenantTableBuilderTest extends TestCase
{
    /**
     * Test that create() returns a new instance.
     */
    public function testCreateReturnsNewInstance(): void
    {
        $builder = TenantTableBuilder::create();

        $this->assertInstanceOf(TenantTableBuilder::class, $builder);
        $this->assertInstanceOf(TableBuilderInterface::class, $builder);
    }

    /**
     * Test that create() returns different instances.
     */
    public function testCreateReturnsDifferentInstances(): void
    {
        $builder1 = TenantTableBuilder::create();
        $builder2 = TenantTableBuilder::create();

        $this->assertNotSame($builder1, $builder2);
    }

    /**
     * Test after() method sets column name and returns fluent interface.
     */
    public function testAfterSetsColumnAndReturnsFluentInterface(): void
    {
        $builder = TenantTableBuilder::create();
        $result = $builder->after('created_at');

        $this->assertSame($builder, $result);

        $config = $builder->build();
        $this->assertEquals('created_at', $config->after);
    }

    /**
     * Test after() method with different column names.
     */
    public function testAfterWithDifferentColumns(): void
    {
        $testCases = [
            'id',
            'uuid',
            'created_at',
            'updated_at',
            'custom_column',
        ];

        foreach ($testCases as $column) {
            $builder = TenantTableBuilder::create()->after($column);
            $config = $builder->build();

            $this->assertEquals($column, $config->after);
        }
    }

    /**
     * Test cascadeDelete() method with default parameter.
     */
    public function testCascadeDeleteWithDefaultParameter(): void
    {
        $builder = TenantTableBuilder::create();
        $result = $builder->cascadeDelete();

        $this->assertSame($builder, $result);

        $config = $builder->build();
        $this->assertTrue($config->cascadeDelete);
    }

    /**
     * Test cascadeDelete() method with explicit true.
     */
    public function testCascadeDeleteWithExplicitTrue(): void
    {
        $builder = TenantTableBuilder::create();
        $result = $builder->cascadeDelete(true);

        $this->assertSame($builder, $result);

        $config = $builder->build();
        $this->assertTrue($config->cascadeDelete);
    }

    /**
     * Test cascadeDelete() method with false.
     */
    public function testCascadeDeleteWithFalse(): void
    {
        $builder = TenantTableBuilder::create();
        $result = $builder->cascadeDelete(false);

        $this->assertSame($builder, $result);

        $config = $builder->build();
        $this->assertFalse($config->cascadeDelete);
    }

    /**
     * Test dropUnique() method adds single constraint.
     */
    public function testDropUniqueAddsSingleConstraint(): void
    {
        $builder = TenantTableBuilder::create();
        $columns = ['email', 'domain'];
        $result = $builder->dropUnique($columns);

        $this->assertSame($builder, $result);

        $config = $builder->build();
        $this->assertEquals([$columns], $config->dropUniques);
    }

    /**
     * Test dropUnique() method with multiple calls.
     */
    public function testDropUniqueWithMultipleCalls(): void
    {
        $builder = TenantTableBuilder::create();
        $constraint1 = ['email'];
        $constraint2 = ['username', 'domain'];
        $constraint3 = ['slug'];

        $builder->dropUnique($constraint1)
               ->dropUnique($constraint2)
               ->dropUnique($constraint3);

        $config = $builder->build();
        $this->assertEquals([$constraint1, $constraint2, $constraint3], $config->dropUniques);
    }

    /**
     * Test dropUniques() method with multiple constraints.
     */
    public function testDropUniquesWithMultipleConstraints(): void
    {
        $builder = TenantTableBuilder::create();
        $constraints = [
            ['email'],
            ['username', 'domain'],
            ['slug', 'type'],
        ];
        $result = $builder->dropUniques($constraints);

        $this->assertSame($builder, $result);

        $config = $builder->build();
        $this->assertEquals($constraints, $config->dropUniques);
    }

    /**
     * Test dropUniques() method with empty array.
     */
    public function testDropUniquesWithEmptyArray(): void
    {
        $builder = TenantTableBuilder::create();
        $result = $builder->dropUniques([]);

        $this->assertSame($builder, $result);

        $config = $builder->build();
        $this->assertEquals([], $config->dropUniques);
    }

    /**
     * Test tenantUnique() method adds single constraint.
     */
    public function testTenantUniqueAddsSingleConstraint(): void
    {
        $builder = TenantTableBuilder::create();
        $columns = ['email'];
        $result = $builder->tenantUnique($columns);

        $this->assertSame($builder, $result);

        $config = $builder->build();
        $this->assertEquals([$columns], $config->tenantUniqueConstraints);
    }

    /**
     * Test tenantUnique() method with multiple calls.
     */
    public function testTenantUniqueWithMultipleCalls(): void
    {
        $builder = TenantTableBuilder::create();
        $constraint1 = ['email'];
        $constraint2 = ['username'];
        $constraint3 = ['slug', 'type'];

        $builder->tenantUnique($constraint1)
               ->tenantUnique($constraint2)
               ->tenantUnique($constraint3);

        $config = $builder->build();
        $this->assertEquals([$constraint1, $constraint2, $constraint3], $config->tenantUniqueConstraints);
    }

    /**
     * Test tenantUniques() method with multiple constraints.
     */
    public function testTenantUniquesWithMultipleConstraints(): void
    {
        $builder = TenantTableBuilder::create();
        $constraints = [
            ['email'],
            ['username'],
            ['slug', 'status'],
        ];
        $result = $builder->tenantUniques($constraints);

        $this->assertSame($builder, $result);

        $config = $builder->build();
        $this->assertEquals($constraints, $config->tenantUniqueConstraints);
    }

    /**
     * Test tenantUniques() method with empty array.
     */
    public function testTenantUniquesWithEmptyArray(): void
    {
        $builder = TenantTableBuilder::create();
        $result = $builder->tenantUniques([]);

        $this->assertSame($builder, $result);

        $config = $builder->build();
        $this->assertEquals([], $config->tenantUniqueConstraints);
    }

    /**
     * Test build() method returns TenantTableConfig with default values.
     */
    public function testBuildReturnsConfigWithDefaults(): void
    {
        $builder = TenantTableBuilder::create();
        $config = $builder->build();

        $this->assertInstanceOf(TenantTableConfig::class, $config);
        $this->assertEquals('id', $config->after);
        $this->assertTrue($config->cascadeDelete);
        $this->assertEquals([], $config->dropUniques);
        $this->assertEquals([], $config->tenantUniqueConstraints);
    }

    /**
     * Test build() method returns TenantTableConfig with all configured values.
     */
    public function testBuildReturnsConfigWithAllValues(): void
    {
        $builder = TenantTableBuilder::create();
        $dropConstraints = [['email'], ['username', 'domain']];
        $tenantConstraints = [['slug'], ['name', 'type']];

        $config = $builder
            ->after('created_at')
            ->cascadeDelete(false)
            ->dropUniques($dropConstraints)
            ->tenantUniques($tenantConstraints)
            ->build();

        $this->assertInstanceOf(TenantTableConfig::class, $config);
        $this->assertEquals('created_at', $config->after);
        $this->assertFalse($config->cascadeDelete);
        $this->assertEquals($dropConstraints, $config->dropUniques);
        $this->assertEquals($tenantConstraints, $config->tenantUniqueConstraints);
    }

    /**
     * Test fluent interface chaining.
     */
    public function testFluentInterfaceChaining(): void
    {
        $builder = TenantTableBuilder::create();

        $result = $builder
            ->after('uuid')
            ->cascadeDelete(false)
            ->dropUnique(['email'])
            ->dropUnique(['username'])
            ->tenantUnique(['slug'])
            ->tenantUnique(['name', 'type']);

        $this->assertSame($builder, $result);

        $config = $result->build();
        $this->assertEquals('uuid', $config->after);
        $this->assertFalse($config->cascadeDelete);
        $this->assertEquals([['email'], ['username']], $config->dropUniques);
        $this->assertEquals([['slug'], ['name', 'type']], $config->tenantUniqueConstraints);
    }

    /**
     * Test mixed usage of individual and bulk methods.
     */
    public function testMixedIndividualAndBulkMethods(): void
    {
        $builder = TenantTableBuilder::create();

        $builder
            ->dropUnique(['email'])
            ->dropUniques([['username'], ['domain']])
            ->dropUnique(['slug'])
            ->tenantUnique(['name'])
            ->tenantUniques([['type'], ['status']])
            ->tenantUnique(['category']);

        $config = $builder->build();

        $expectedDropUniques = [['email'], ['username'], ['domain'], ['slug']];
        $expectedTenantUniques = [['name'], ['type'], ['status'], ['category']];

        $this->assertEquals($expectedDropUniques, $config->dropUniques);
        $this->assertEquals($expectedTenantUniques, $config->tenantUniqueConstraints);
    }

    /**
     * Test that build() can be called multiple times.
     */
    public function testBuildCanBeCalledMultipleTimes(): void
    {
        $builder = TenantTableBuilder::create()
            ->after('uuid')
            ->cascadeDelete(false);

        $config1 = $builder->build();
        $config2 = $builder->build();

        $this->assertEquals($config1->after, $config2->after);
        $this->assertEquals($config1->cascadeDelete, $config2->cascadeDelete);
        $this->assertEquals($config1->dropUniques, $config2->dropUniques);
        $this->assertEquals($config1->tenantUniqueConstraints, $config2->tenantUniqueConstraints);
    }

    /**
     * Test that modifications after build() affect subsequent builds.
     */
    public function testModificationsAfterBuildAffectSubsequentBuilds(): void
    {
        $builder = TenantTableBuilder::create()->after('id');
        $config1 = $builder->build();

        $builder->after('uuid');
        $config2 = $builder->build();

        $this->assertEquals('id', $config1->after);
        $this->assertEquals('uuid', $config2->after);
    }
}
