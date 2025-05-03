<?php

namespace Modules\Core\Tests\Unit\Traits;

use Modules\Core\Contracts\TranslatableEntity;
use Modules\Core\Traits\RendersBadRequest;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Tests\TestCase;

#[CoversClass(RendersBadRequest::class)]
#[Group('core-module')]
#[Group('core-traits')]
class RendersBadRequestTest extends TestCase
{
    /**
     * Test that render() returns a JSON response with the translated message
     * when the exception implements TranslatableEntity.
     */
    public function testRenderReturnsTranslatedMessageWhenExceptionIsTranslatable(): void
    {
        $exception = new class () extends Exception implements TranslatableEntity {
            use RendersBadRequest;

            public function getTranslatedMessage(): string
            {
                return 'Translated error message';
            }
        };

        $response = $exception->render();

        $this->assertEquals(ResponseAlias::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals(['message' => 'Translated error message'], $response->getData(true));
    }

    /**
     * Test that render() returns a JSON response with the default message
     * when the exception does not implement TranslatableEntity.
     */
    public function testRenderReturnsDefaultMessageWhenExceptionIsNotTranslatable(): void
    {
        $exception = new class ('Default error message') extends Exception {
            use RendersBadRequest;
        };

        $response = $exception->render();

        $this->assertEquals(ResponseAlias::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals(['message' => 'Default error message'], $response->getData(true));
    }
}
