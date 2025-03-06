<?php

namespace Tests\Unit\Traits;

use App\Contracts\TranslatableException;
use App\Traits\RendersBadRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(RendersBadRequest::class)]
#[Group('app-traits')]
class RendersBadRequestTest extends TestCase
{
    /**
     * Test that render() returns a JSON response with the translated message
     * when the exception implements TranslatableException.
     */
    public function testRenderReturnsTranslatedMessageWhenExceptionIsTranslatable(): void
    {
        $exception = new class extends \Exception implements TranslatableException
        {
            use RendersBadRequest;

            public function getTranslatedMessage(): string
            {
                return 'Translated error message';
            }
        };

        $response = $exception->render();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals(['message' => 'Translated error message'], $response->getData(true));
    }

    /**
     * Test that render() returns a JSON response with the default message
     * when the exception does not implement TranslatableException.
     */
    public function testRenderReturnsDefaultMessageWhenExceptionIsNotTranslatable(): void
    {
        $exception = new class ('Default error message') extends \Exception
        {
            use RendersBadRequest;
        };

        $response = $exception->render();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals(['message' => 'Default error message'], $response->getData(true));
    }
}
