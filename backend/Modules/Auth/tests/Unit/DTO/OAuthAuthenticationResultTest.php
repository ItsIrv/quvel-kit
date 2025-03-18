<?php

namespace Modules\Auth\Tests\Unit\DTO;

use App\Models\User;
use Modules\Auth\DTO\OAuthAuthenticationResult;
use Modules\Auth\Enums\OAuthStatusEnum;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(OAuthAuthenticationResult::class)]
#[Group('auth-module')]
#[Group('auth-dto')]
class OAuthAuthenticationResultTest extends TestCase
{
    /**
     * Data provider for different statuses + stateless vs. stateful scenarios.
     *
     * @return array<string, array{status: OAuthStatusEnum, signedNonce: ?string, expectedIsStateless: bool}>
     */
    public static function statusProvider(): array
    {
        return [
            'login_ok_stateless' => [
                'status' => OAuthStatusEnum::LOGIN_OK,
                'signedNonce' => 'abc123',
                'expectedIsStateless' => true,
            ],
            'user_created_stateful' => [
                'status' => OAuthStatusEnum::USER_CREATED,
                'signedNonce' => null,
                'expectedIsStateless' => false,
            ],
            'email_not_verified_stateless' => [
                'status' => OAuthStatusEnum::EMAIL_NOT_VERIFIED,
                'signedNonce' => 'nonce-xyz',
                'expectedIsStateless' => true,
            ],
            'email_taken_stateful' => [
                'status' => OAuthStatusEnum::EMAIL_TAKEN,
                'signedNonce' => null,
                'expectedIsStateless' => false,
            ],
        ];
    }

    #[DataProvider('statusProvider')]
    public function test_oauth_authentication_result(
        OAuthStatusEnum $status,
        ?string $signedNonce,
        bool $expectedIsStateless
    ): void {
        // Arrange
        $user = new User(['id' => 99]);

        // Act
        $result = new OAuthAuthenticationResult($user, $status, $signedNonce);

        // Assert
        $this->assertSame($user, $result->getUser());
        $this->assertSame($status, $result->getStatus());
        $this->assertSame($signedNonce, $result->getSignedNonce());
        $this->assertEquals($expectedIsStateless, $result->isStateless());
    }
}
