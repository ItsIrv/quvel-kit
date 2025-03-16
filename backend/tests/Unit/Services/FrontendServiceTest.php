<?php

namespace Tests\Unit\Services;

use App\Services\FrontendService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(FrontendService::class)]
#[Group('frontend')]
#[Group('services')]
class FrontendServiceTest extends TestCase
{
    private FrontendService $frontendService;

    private string $baseUrl = 'https://quvel.127.0.0.1.nip.io';

    #[Before]
    public function setupTest(): void
    {
        $this->frontendService = new FrontendService($this->baseUrl);
    }

    /**
     * Mock a redirect and assert response.
     */
    private function assertRedirect(string $expectedUrl, callable $callback): void
    {
        Redirect::spy();

        Redirect::shouldReceive('away')
            ->once()
            ->with($expectedUrl)
            ->andReturn(new RedirectResponse($expectedUrl));

        $response = $callback();

        $this->assertInstanceOf(
            RedirectResponse::class,
            $response,
        );

        $this->assertEquals(
            $expectedUrl,
            $response->getTargetUrl(),
        );
    }

    /**
     * Test redirect to a specific frontend route.
     */
    public function test_redirect(): void
    {
        $this->assertRedirect(
            "$this->baseUrl/dashboard",
            fn () => $this->frontendService->redirect('/dashboard'),
        );
    }

    /**
     * Test redirect to a frontend page with query parameters.
     */
    public function test_redirect_page_with_params(): void
    {
        $params = ['id' => 42, 'mode' => 'edit'];

        $this->assertRedirect(
            "$this->baseUrl/profile?".http_build_query($params),
            fn () => $this->frontendService->redirectPage(
                'profile',
                $params,
            ),
        );
    }

    /**
     * Test getting a full frontend page URL.
     */
    public function test_get_page_url(): void
    {
        $params = ['theme' => 'dark'];
        $expectedUrl = "$this->baseUrl/settings?".http_build_query($params);

        $this->assertEquals(
            $expectedUrl,
            $this->frontendService->getPageUrl('settings', $params),
        );
    }
}
