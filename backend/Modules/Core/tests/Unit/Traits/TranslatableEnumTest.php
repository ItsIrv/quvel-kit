<?php

namespace Modules\Core\Tests\Unit\Traits;

use Modules\Core\Traits\TranslatableEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Lang;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(TranslatableEnum::class)]
#[Group('core-module')]
#[Group('core-traits')]
class TranslatableEnumTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that getTranslatedMessage returns the correct translation.
     */
    public function testGetTranslatedMessage(): void
    {
        $enumInstance = new class () {
            use TranslatableEnum;

            public $value = 'example_message';
        };

        // Mock the translation function correctly
        Lang::shouldReceive('get')
            ->once()
            ->with('example_message', [], null)
            ->andReturn('Example Message');

        $this->assertEquals(
            'Example Message',
            $enumInstance->getTranslatedMessage(),
        );
    }
}
