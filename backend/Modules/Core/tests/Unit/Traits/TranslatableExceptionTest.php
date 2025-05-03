<?php

namespace Tests\Unit\Traits;

use Modules\Core\Contracts\TranslatableEntity;
use Modules\Core\Traits\TranslatableException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(TranslatableException::class)]
#[Group('core-module')]
#[Group('core-traits')]
class TranslatableExceptionTest extends TestCase
{
    /**
     * Test that getTranslatedMessage() returns a valid translation string.
     */
    public function testGetTranslatedMessageReturnsValidTranslation(): void
    {
        // Mock an exception class that uses the trait
        $exception = new class ('validation.required') extends \Exception {
            use TranslatableException;
        };

        // Mock Laravel's translation helper function
        app('translator')->addLines([
            'validation.required' => 'This field is required.',
        ], 'en');

        $this->assertEquals('This field is required.', $exception->getTranslatedMessage());
    }

    /**
     * Test that getTranslatedMessage() returns an empty string for missing translation.
     */
    public function testGetTranslatedMessageReturnsEmptyStringWhenTranslationNotFound(): void
    {
        // Mock an exception class that uses the trait
        $exception = new class ('missing.translation.key') extends \Exception {
            use TranslatableException;
        };

        $this->assertEquals('missing.translation.key', $exception->getTranslatedMessage());
    }

    /**
     * Test that getTranslatedMessage() is called on message if it implements TranslatableEntity.
     */
    public function testGetTranslatedMessageCalledOnTranslatableEntity(): void
    {
        // Create a mock for TranslatableEntity
        $translatableEntityMock = $this->createMock(
            TranslatableEntity::class,
        );

        // Set up the mock to expect getTranslatedMessage() to be called
        $translatableEntityMock->expects($this->once())
            ->method('getTranslatedMessage')
            ->willReturn('Translated Message');

        // Mock an exception class that uses the trait
        $exception = new class ($translatableEntityMock) {
            use TranslatableException;

            public function __construct(protected TranslatableEntity $message)
            {
            }
        };

        $this->assertEquals('Translated Message', $exception->getTranslatedMessage());
    }
}
