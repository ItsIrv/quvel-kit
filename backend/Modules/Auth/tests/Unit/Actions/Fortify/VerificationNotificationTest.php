<?php

namespace Modules\Auth\Tests\Unit\Actions\Fortify;

use Illuminate\Http\RedirectResponse;
use Modules\Auth\Actions\Fortify\VerificationNotification;
use Modules\Auth\Enums\EmailStatusEnum;
use Modules\Auth\Http\Requests\EmailNotificationRequest;
use Modules\Core\Services\FrontendService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(VerificationNotification::class)]
#[Group('auth-module')]
#[Group('auth-actions')]
class VerificationNotificationTest extends TestCase
{
    /**
     * Test that the notification action redirects to the frontend with success message when notification is sent successfully.
     */
    public function testNotificationSuccessful(): void
    {
        // Arrange
        $request = $this->createMock(EmailNotificationRequest::class);
        $request->expects($this->once())
            ->method('fulfill');

        $redirectResponse = $this->createMock(RedirectResponse::class);

        $frontendService = $this->createMock(FrontendService::class);
        $frontendService->expects($this->once())
            ->method('redirect')
            ->with('', [
                'message' => EmailStatusEnum::EMAIL_VERIFICATION_NOTICE->value,
            ])
            ->willReturn($redirectResponse);

        $action = new VerificationNotification($frontendService);

        // Act
        $result = $action($request);

        // Assert
        $this->assertSame($redirectResponse, $result);
    }
}
