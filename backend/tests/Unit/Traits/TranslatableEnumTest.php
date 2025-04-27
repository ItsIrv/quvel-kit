<?php

namespace Tests\Unit\Traits;

use App\Traits\TranslatableEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Lang;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

// ✅ Laravel TestCase, not PHPUnit's

#[CoversClass(TranslatableEnum::class)]
#[Group('app-traits')]
class TranslatableEnumTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that getTranslatedMessage returns the correct translation.
     */
    public function test_get_translated_message(): void
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
