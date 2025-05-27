<?php

namespace Modules\Auth\Tests\Unit\Actions\Fortify;

use Exception;
use Illuminate\Http\RedirectResponse;
use Modules\Auth\Actions\Fortify\VerificationVerify;
use Modules\Auth\Enums\EmailStatusEnum;
use Modules\Auth\Http\Requests\EmailVerificationRequest;
use Modules\Core\Enums\StatusEnum;
use Modules\Core\Services\FrontendService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;
use Mockery;

#[CoversClass(VerificationVerify::class)]
#[Group('auth-module')]
#[Group('auth-actions')]
class VerificationVerifyTest extends TestCase
{
    private VerificationVerify $action;
    private FrontendService $frontendService;
    private EmailVerificationRequest $request;
    private RedirectResponse $redirectResponse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->frontendService  = Mockery::mock(FrontendService::class);
        $this->request          = Mockery::mock(EmailVerificationRequest::class);
        $this->redirectResponse = Mockery::mock(RedirectResponse::class);

        $this->action = new VerificationVerify($this->frontendService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[TestDox('constructs with required dependencies')]
    public function testConstructsWithRequiredDependencies(): void
    {
        $action = new VerificationVerify($this->frontendService);

        $this->assertInstanceOf(VerificationVerify::class, $action);
    }

    #[TestDox('successfully verifies email and redirects with success message')]
    public function testSuccessfullyVerifiesEmailAndRedirectsWithSuccessMessage(): void
    {
        $this->request
            ->shouldReceive('fulfill')
            ->once();

        $this->frontendService
            ->shouldReceive('redirect')
            ->once()
            ->with('', [
                'message' => EmailStatusEnum::EMAIL_VERIFIED->value,
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
                    return $data['message'] === EmailStatusEnum::EMAIL_VERIFIED->value;
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

    #[TestDox('uses email verified enum value specifically for verification success')]
    public function testUsesEmailVerifiedEnumValueSpecificallyForVerificationSuccess(): void
    {
        $this->request
            ->shouldReceive('fulfill');

        $this->frontendService
            ->shouldReceive('redirect')
            ->with('', [
                'message' => EmailStatusEnum::EMAIL_VERIFIED->value,
            ])
            ->andReturn($this->redirectResponse);

        $result = ($this->action)($this->request);
        
        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    #[TestDox('follows same pattern as verification notification but with different success message')]
    public function testFollowsSamePatternAsVerificationNotificationButWithDifferentSuccessMessage(): void
    {
        $this->request
            ->shouldReceive('fulfill');

        $this->frontendService
            ->shouldReceive('redirect')
            ->with(
                '',
                Mockery::on(function ($data) {
                    return isset($data['message']) &&
                           $data['message'] === EmailStatusEnum::EMAIL_VERIFIED->value &&
                           $data['message'] !== EmailStatusEnum::EMAIL_VERIFICATION_NOTICE->value;
                })
            )
            ->andReturn($this->redirectResponse);

        $result = ($this->action)($this->request);
        
        $this->assertInstanceOf(RedirectResponse::class, $result);
    }
}