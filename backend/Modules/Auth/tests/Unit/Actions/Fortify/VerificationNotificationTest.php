<?php

namespace Modules\Auth\Tests\Unit\Actions\Fortify;

use Exception;
use Illuminate\Http\RedirectResponse;
use Modules\Auth\Actions\Fortify\VerificationNotification;
use Modules\Auth\Enums\EmailStatusEnum;
use Modules\Auth\Http\Requests\EmailNotificationRequest;
use Modules\Core\Enums\StatusEnum;
use Modules\Core\Services\FrontendService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;
use Mockery;

#[CoversClass(VerificationNotification::class)]
#[Group('auth-module')]
#[Group('auth-actions')]
class VerificationNotificationTest extends TestCase
{
    private VerificationNotification $action;
    private FrontendService $frontendService;
    private EmailNotificationRequest $request;
    private RedirectResponse $redirectResponse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->frontendService  = Mockery::mock(FrontendService::class);
        $this->request          = Mockery::mock(EmailNotificationRequest::class);
        $this->redirectResponse = Mockery::mock(RedirectResponse::class);

        $this->action = new VerificationNotification($this->frontendService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[TestDox('constructs with required dependencies')]
    public function testConstructsWithRequiredDependencies(): void
    {
        $action = new VerificationNotification($this->frontendService);

        $this->assertInstanceOf(VerificationNotification::class, $action);
    }

    #[TestDox('successfully sends email verification notification and redirects with success message')]
    public function testSuccessfullySendsEmailVerificationNotificationAndRedirectsWithSuccessMessage(): void
    {
        $this->request
            ->shouldReceive('fulfill')
            ->once();

        $this->frontendService
            ->shouldReceive('redirect')
            ->once()
            ->with('', [
                'message' => EmailStatusEnum::EMAIL_VERIFICATION_NOTICE->value,
            ])
            ->andReturn($this->redirectResponse);

        $result = ($this->action)($this->request);

        $this->assertSame($this->redirectResponse, $result);
    }

    #[TestDox('catches exception and redirects with error message when request fulfill fails')]
    public function testCatchesExceptionAndRedirectsWithErrorMessageWhenRequestFulfillFails(): void
    {
        $exception = new Exception('Request fulfill failed');

        $this->request
            ->shouldReceive('fulfill')
            ->once()
            ->andThrow($exception);

        $this->frontendService
            ->shouldReceive('redirect')
            ->once()
            ->with('', [
                'message' => StatusEnum::INTERNAL_ERROR->value,
            ])
            ->andReturn($this->redirectResponse);

        $result = ($this->action)($this->request);

        $this->assertSame($this->redirectResponse, $result);
    }

    #[TestDox('redirects to empty route on success')]
    public function testRedirectsToEmptyRouteOnSuccess(): void
    {
        $this->request
            ->shouldReceive('fulfill');

        $this->frontendService
            ->shouldReceive('redirect')
            ->once()
            ->with(
                Mockery::on(function ($route) {
                    return $route === '';
                }),
                Mockery::any()
            )
            ->andReturn($this->redirectResponse);

        $result = ($this->action)($this->request);
        
        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    #[TestDox('redirects to empty route on error')]
    public function testRedirectsToEmptyRouteOnError(): void
    {
        $this->request
            ->shouldReceive('fulfill')
            ->andThrow(new Exception('Test exception'));

        $this->frontendService
            ->shouldReceive('redirect')
            ->once()
            ->with(
                Mockery::on(function ($route) {
                    return $route === '';
                }),
                Mockery::any()
            )
            ->andReturn($this->redirectResponse);

        $result = ($this->action)($this->request);
        
        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    #[TestDox('uses correct email status enum for success message')]
    public function testUsesCorrectEmailStatusEnumForSuccessMessage(): void
    {
        $this->request
            ->shouldReceive('fulfill');

        $this->frontendService
            ->shouldReceive('redirect')
            ->once()
            ->with(
                Mockery::any(),
                Mockery::on(function ($data) {
                    return $data['message'] === EmailStatusEnum::EMAIL_VERIFICATION_NOTICE->value;
                })
            )
            ->andReturn($this->redirectResponse);

        $result = ($this->action)($this->request);
        
        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    #[TestDox('uses correct status enum for error message')]
    public function testUsesCorrectStatusEnumForErrorMessage(): void
    {
        $this->request
            ->shouldReceive('fulfill')
            ->andThrow(new Exception('Test exception'));

        $this->frontendService
            ->shouldReceive('redirect')
            ->once()
            ->with(
                Mockery::any(),
                Mockery::on(function ($data) {
                    return $data['message'] === StatusEnum::INTERNAL_ERROR->value;
                })
            )
            ->andReturn($this->redirectResponse);

        $result = ($this->action)($this->request);
        
        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    #[TestDox('is callable and returns redirect response')]
    public function testIsCallableAndReturnsRedirectResponse(): void
    {
        $this->request
            ->shouldReceive('fulfill');

        $this->frontendService
            ->shouldReceive('redirect')
            ->andReturn($this->redirectResponse);

        $result = ($this->action)($this->request);

        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    #[TestDox('handles any exception type gracefully')]
    public function testHandlesAnyExceptionTypeGracefully(): void
    {
        $exception = new \RuntimeException('Runtime error');

        $this->request
            ->shouldReceive('fulfill')
            ->andThrow($exception);

        $this->frontendService
            ->shouldReceive('redirect')
            ->andReturn($this->redirectResponse);

        $result = ($this->action)($this->request);

        $this->assertInstanceOf(RedirectResponse::class, $result);
    }
}