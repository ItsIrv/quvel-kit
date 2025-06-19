<?php

namespace Modules\Core\Tests\Unit\Http\Middleware\Lang;

use Closure;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Mockery;
use Mockery\MockInterface;
use Modules\Core\Enums\CoreHeader;
use Modules\Core\Http\Middleware\Lang\SetRequestLocale;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

#[CoversClass(SetRequestLocale::class)]
#[Group('core-module')]
#[Group('core-middleware')]
final class SetRequestLocaleTest extends TestCase
{
    /**
     * Request mock instance.
     */
    private Request|MockInterface $request;

    /**
     * ConfigRepository mock instance.
     */
    private ConfigRepository|MockInterface $config;

    /**
     * Application mock instance.
     */
    private Application|MockInterface $application;

    /**
     * Next closure.
     */
    private Closure $next;

    /**
     * Middleware instance.
     */
    private SetRequestLocale $middleware;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->request = Mockery::mock(Request::class);
        $this->config = Mockery::mock(ConfigRepository::class);
        $this->application = Mockery::mock(Application::class);
        $this->next = function ($request) {
            return response('next was called');
        };

        // Bind mocks to the container
        app()->instance(ConfigRepository::class, $this->config);
        app()->instance(Application::class, $this->application);

        $this->middleware = new SetRequestLocale();
    }

    #[TestDox('It should set locale when header is present and allowed')]
    public function testSetsLocaleWhenHeaderIsPresentAndAllowed(): void
    {
        // Arrange
        $locale = 'fr-FR';
        $normalizedLocale = 'fr';
        $allowedLocales = ['en-US', 'fr-FR', 'es-ES'];

        $this->config->shouldReceive('get')
            ->once()
            ->with('frontend.allowed_locales', ['en-US'])
            ->andReturn($allowedLocales);

        $this->request->shouldReceive('header')
            ->once()
            ->with(CoreHeader::ACCEPT_LANGUAGE->value)
            ->andReturn($locale);

        $this->application->shouldReceive('setLocale')
            ->once()
            ->with($normalizedLocale);

        $this->request->shouldReceive('setLocale')
            ->once()
            ->with($normalizedLocale);

        // Act
        $response = $this->middleware->handle($this->request, $this->next);

        // Assert
        $this->assertEquals('next was called', $response->getContent());
    }

    #[TestDox('It should not set locale when header is not present')]
    public function testDoesNotSetLocaleWhenHeaderIsNotPresent(): void
    {
        // Arrange
        $allowedLocales = ['en-US', 'fr-FR', 'es-ES'];

        $this->config->shouldReceive('get')
            ->once()
            ->with('frontend.allowed_locales', ['en-US'])
            ->andReturn($allowedLocales);

        $this->request->shouldReceive('header')
            ->once()
            ->with(CoreHeader::ACCEPT_LANGUAGE->value)
            ->andReturn(null);

        // App and request should not receive setLocale calls
        $this->application->shouldNotReceive('setLocale');
        $this->request->shouldNotReceive('setLocale');

        // Act
        $response = $this->middleware->handle($this->request, $this->next);

        // Assert
        $this->assertEquals('next was called', $response->getContent());
    }

    #[TestDox('It should not set locale when header is not in allowed locales')]
    public function testDoesNotSetLocaleWhenHeaderIsNotInAllowedLocales(): void
    {
        // Arrange
        $locale = 'de-DE';
        $allowedLocales = ['en-US', 'fr-FR', 'es-ES'];

        $this->config->shouldReceive('get')
            ->once()
            ->with('frontend.allowed_locales', ['en-US'])
            ->andReturn($allowedLocales);

        $this->request->shouldReceive('header')
            ->once()
            ->with(CoreHeader::ACCEPT_LANGUAGE->value)
            ->andReturn($locale);

        // App and request should not receive setLocale calls
        $this->application->shouldNotReceive('setLocale');
        $this->request->shouldNotReceive('setLocale');

        // Act
        $response = $this->middleware->handle($this->request, $this->next);

        // Assert
        $this->assertEquals('next was called', $response->getContent());
    }

    #[TestDox('It should normalize locale correctly')]
    public function testNormalizesLocaleCorrectly(): void
    {
        // Arrange - Testing multiple locale formats
        $testCases = [
            'en-US' => 'en',
            'fr-FR' => 'fr',
            'es-MX' => 'es',
            'pt-BR' => 'pt',
        ];

        $allowedLocales = array_keys($testCases);

        $this->config->shouldReceive('get')
            ->times(count($testCases))
            ->with('frontend.allowed_locales', ['en-US'])
            ->andReturn($allowedLocales);

        foreach ($testCases as $locale => $normalizedLocale) {
            $this->request->shouldReceive('header')
                ->once()
                ->with(CoreHeader::ACCEPT_LANGUAGE->value)
                ->andReturn($locale);

            $this->application->shouldReceive('setLocale')
                ->once()
                ->with($normalizedLocale);

            $this->request->shouldReceive('setLocale')
                ->once()
                ->with($normalizedLocale);

            // Act
            $response = $this->middleware->handle($this->request, $this->next);

            // Assert
            $this->assertEquals('next was called', $response->getContent());
        }
    }
}
