<?php

namespace Modules\Auth\Tests\Unit\Actions\Fortify;

use Illuminate\Http\RedirectResponse;
use Modules\Auth\Actions\Fortify\VerificationVerify;
use Modules\Auth\Enums\EmailStatusEnum;
use Modules\Auth\Http\Requests\EmailVerificationRequest;
use Modules\Core\Services\FrontendService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(VerificationVerify::class)]
#[Group('auth-module')]
#[Group('auth-actions')]
class VerificationVerifyTest extends TestCase
{
    /**
     * Test that the verification action redirects to the frontend with success message when verification is successful.
     */
    public function testVerificationSuccessful(): void
    {
        // Arrange
        $request = $this->createMock(EmailVerificationRequest::class);
        $request->expects($this->once())
            ->method('fulfill');

        $redirectResponse = $this->createMock(RedirectResponse::class);

        $frontendService = $this->createMock(FrontendService::class);
        $frontendService->expects($this->once())
            ->method('redirect')
            ->with('', [
                'message' => EmailStatusEnum::EMAIL_VERIFIED->value,
            ])
            ->willReturn($redirectResponse);

        $action = new VerificationVerify($frontendService);

        // Act
        $result = $action($request);

        // Assert
        $this->assertSame($redirectResponse, $result);
    }
}
