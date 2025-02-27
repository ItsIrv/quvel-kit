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
    public function testRedirect(): void
    {
        $this->assertRedirect(
            "{$this->baseUrl}/dashboard",
            fn () => $this->frontendService->redirect('/dashboard'),
        );
    }

    /**
     * Test redirect to success page with a message.
     */
    public function testRedirectSuccess(): void
    {
        $this->assertRedirect(
            "{$this->baseUrl}/success?message=" . urlencode('Operation successful'),
            fn () => $this->frontendService->redirectSuccess(
                'Operation successful',
            ),
        );
    }

    /**
     * Test redirect to an error page with a message.
     */
    public function testRedirectError(): void
    {
        $this->assertRedirect(
            "{$this->baseUrl}/error?message=" . urlencode('Something went wrong'),
            fn () => $this->frontendService->redirectError(
                'Something went wrong',
            ),
        );
    }

    /**
     * Test redirect to a frontend page with query parameters.
     */
    public function testRedirectPageWithParams(): void
    {
        $params = ['id' => 42, 'mode' => 'edit'];

        $this->assertRedirect(
            "{$this->baseUrl}/profile?" . http_build_query($params),
            fn () => $this->frontendService->redirectPage(
                'profile',
                $params,
            ),
        );
    }

    /**
     * Test redirect to login page with parameters.
     */
    public function testRedirectLogin(): void
    {
        $params = ['next' => 'dashboard'];

        $this->assertRedirect(
            "{$this->baseUrl}/login?" . http_build_query($params),
            fn () => $this->frontendService->redirectLogin($params),
        );
    }

    /**
     * Test redirect to login with status messages.
     */
    public function testRedirectLoginStatus(): void
    {
        $this->assertRedirect(
            "{$this->baseUrl}/login?type=error&message=" . urlencode('Invalid credentials'),
            fn () => $this->frontendService->redirectLoginStatus(
                'error',
                'Invalid credentials',
            ),
        );
    }

    /**
     * Test getting a full frontend page URL.
     */
    public function testGetPageUrl(): void
    {
        $params      = ['theme' => 'dark'];
        $expectedUrl = "{$this->baseUrl}/settings?" . http_build_query($params);

        $this->assertEquals(
            $expectedUrl,
            $this->frontendService->getPageUrl('settings', $params),
        );
    }
}
