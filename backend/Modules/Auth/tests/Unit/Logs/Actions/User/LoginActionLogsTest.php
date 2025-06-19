<?php

namespace Modules\Auth\Tests\Unit\Logs\Actions\User;

use Illuminate\Log\LogManager;
use Mockery;
use Modules\Auth\Logs\Actions\User\LoginActionLogs;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(LoginActionLogs::class)]
#[Group('auth-module')]
#[Group('auth-logs')]
class LoginActionLogsTest extends TestCase
{
    /**
     * The mocked log manager.
     */
    private Mockery\MockInterface $logManager;

    /**
     * The login action logs instance.
     */
    private LoginActionLogs $loginActionLogs;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create a mock logger that will capture all log calls
        $this->logManager = Mockery::mock(LogManager::class);

        // Create a partial mock of the LoginActionLogs class
        $this->loginActionLogs = Mockery::mock(LoginActionLogs::class, [$this->logManager])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }

    /**
     * Test that loginSuccess logs with the correct parameters.
     */
    public function testLoginSuccessLogsCorrectly(): void
    {
        // Arrange
        $email     = 'test@example.com';
        $userId    = 123;
        $ipAddress = '127.0.0.1';
        $userAgent = 'PHPUnit Test';

        // Assert
        $this->loginActionLogs->shouldReceive('info')
            ->once()
            ->with('User login successful', [
                'email'      => $email,
                'user_id'    => $userId,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);

        // Act
        $this->loginActionLogs->loginSuccess($email, $userId, $ipAddress, $userAgent);
    }

    /**
     * Test that loginFailedInvalidCredentials logs with the correct parameters.
     */
    public function testLoginFailedInvalidCredentialsLogsCorrectly(): void
    {
        // Arrange
        $email     = 'test@example.com';
        $ipAddress = '127.0.0.1';
        $userAgent = 'PHPUnit Test';

        // Assert
        $this->loginActionLogs->shouldReceive('warning')
            ->once()
            ->with('Login failed: Invalid credentials', [
                'email'      => $email,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'reason'     => 'invalid_credentials',
            ]);

        // Act
        $this->loginActionLogs->loginFailedInvalidCredentials($email, $ipAddress, $userAgent);
    }

    /**
     * Test that loginFailedUserNotFound logs with the correct parameters.
     */
    public function testLoginFailedUserNotFoundLogsCorrectly(): void
    {
        // Arrange
        $email     = 'test@example.com';
        $ipAddress = '127.0.0.1';
        $userAgent = 'PHPUnit Test';

        // Assert
        $this->loginActionLogs->shouldReceive('warning')
            ->once()
            ->with('Login failed: User not found', [
                'email'      => $email,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'reason'     => 'user_not_found',
            ]);

        // Act
        $this->loginActionLogs->loginFailedUserNotFound($email, $ipAddress, $userAgent);
    }

    /**
     * Test that loginFailedAccountInactive logs with the correct parameters.
     */
    public function testLoginFailedAccountInactiveLogsCorrectly(): void
    {
        // Arrange
        $email     = 'test@example.com';
        $userId    = 123;
        $ipAddress = '127.0.0.1';
        $userAgent = 'PHPUnit Test';

        // Assert
        $this->loginActionLogs->shouldReceive('warning')
            ->once()
            ->with('Login failed: Account inactive', [
                'email'      => $email,
                'user_id'    => $userId,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'reason'     => 'account_inactive',
            ]);

        // Act
        $this->loginActionLogs->loginFailedAccountInactive($email, $userId, $ipAddress, $userAgent);
    }
}
