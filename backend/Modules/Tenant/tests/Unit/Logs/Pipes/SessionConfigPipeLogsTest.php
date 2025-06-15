<?php

namespace Modules\Tenant\Tests\Unit\Logs\Pipes;

use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Context;
use Modules\Tenant\Logs\Pipes\SessionConfigPipeLogs;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Mockery;

/**
 * @testdox SessionConfigPipeLogs
 */
#[CoversClass(SessionConfigPipeLogs::class)]
#[Group('tenant')]
#[Group('unit')]
#[Group('logs')]
class SessionConfigPipeLogsTest extends TestCase
{
    private LogManager&MockObject $logManager;
    private LoggerInterface&MockObject $channel;
    private SessionConfigPipeLogs $logger;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock Context facade to prevent "facade root has not been set" errors
        Context::shouldReceive('has')->with('trace_id')->andReturn(false)->byDefault();

        $this->logManager = $this->createMock(LogManager::class);
        $this->channel = $this->createMock(LoggerInterface::class);

        $this->logManager->expects($this->any())
            ->method('channel')
            ->with('stack')
            ->willReturn($this->channel);

        $this->logger = new SessionConfigPipeLogs($this->logManager);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[TestDox('logs driver changed debug message')]
    public function testDriverChanged(): void
    {
        $driver = 'redis';

        $this->channel->expects($this->once())
            ->method('log')
            ->with('debug', "Set session driver: {$driver}", []);

        $this->logger->driverChanged($driver);
    }

    #[TestDox('logs lifetime changed debug message')]
    public function testLifetimeChanged(): void
    {
        $lifetime = 120;

        $this->channel->expects($this->once())
            ->method('log')
            ->with('debug', "Set session lifetime: {$lifetime} minutes", []);

        $this->logger->lifetimeChanged($lifetime);
    }

    #[TestDox('logs encryption changed debug message with $encrypt value')]
    #[DataProvider('encryptionProvider')]
    public function testEncryptionChanged(bool $encrypt, string $expectedText): void
    {
        $this->channel->expects($this->once())
            ->method('log')
            ->with('debug', "Set session encryption: {$expectedText}", []);

        $this->logger->encryptionChanged($encrypt);
    }

    public static function encryptionProvider(): array
    {
        return [
            'encryption enabled' => [true, 'true'],
            'encryption disabled' => [false, 'false'],
        ];
    }

    #[TestDox('logs path changed debug message')]
    public function testPathChanged(): void
    {
        $path = '/sessions';

        $this->channel->expects($this->once())
            ->method('log')
            ->with('debug', "Set session path: {$path}", []);

        $this->logger->pathChanged($path);
    }

    #[TestDox('logs domain changed debug message')]
    public function testDomainChanged(): void
    {
        $domain = '.example.com';

        $this->channel->expects($this->once())
            ->method('log')
            ->with('debug', "Set session domain: {$domain}", []);

        $this->logger->domainChanged($domain);
    }

    #[TestDox('logs cookie name changed debug message with custom/default prefix')]
    #[DataProvider('cookieNameProvider')]
    public function testCookieNameChanged(string $cookie, bool $isCustom, string $expectedPrefix): void
    {
        $this->channel->expects($this->once())
            ->method('log')
            ->with('debug', "Set {$expectedPrefix} session cookie name: {$cookie}", []);

        $this->logger->cookieNameChanged($cookie, $isCustom);
    }

    public static function cookieNameProvider(): array
    {
        return [
            'custom cookie' => ['tenant_session', true, 'custom'],
            'default cookie' => ['laravel_session', false, 'default'],
        ];
    }

    #[TestDox('logs database connection changed debug message')]
    public function testDatabaseConnectionChanged(): void
    {
        $connection = 'tenant_db';

        $this->channel->expects($this->once())
            ->method('log')
            ->with('debug', "Set session database connection to match tenant database: {$connection}", []);

        $this->logger->databaseConnectionChanged($connection);
    }

    #[TestDox('logs applying changes debug message with context')]
    public function testApplyingChanges(): void
    {
        $changesCount = 5;

        $this->channel->expects($this->once())
            ->method('log')
            ->with('debug', 'Applying session configuration changes', [
                'tenant_session.changes_count' => $changesCount,
            ]);

        $this->logger->applyingChanges($changesCount);
    }

    #[TestDox('logs no changes to apply debug message')]
    public function testNoChangesToApply(): void
    {
        $this->channel->expects($this->once())
            ->method('log')
            ->with('debug', 'No session configuration changes to apply', []);

        $this->logger->noChangesToApply();
    }

    #[TestDox('logs session manager rebound debug message')]
    public function testSessionManagerRebound(): void
    {
        $this->channel->expects($this->once())
            ->method('log')
            ->with('debug', 'Rebound session manager with new configuration', []);

        $this->logger->sessionManagerRebound();
    }

    #[TestDox('logs session manager rebind failure error message with exception details')]
    public function testSessionManagerRebindFailed(): void
    {
        $exception = new \RuntimeException('Connection failed');

        $this->channel->expects($this->once())
            ->method('log')
            ->with('error', "Failed to rebind session manager: {$exception->getMessage()}", [
                'tenant_session.exception' => \RuntimeException::class,
                'tenant_session.file' => $exception->getFile(),
                'tenant_session.line' => $exception->getLine(),
            ]);

        $this->logger->sessionManagerRebindFailed($exception);
    }

    #[TestDox('logs session manager not bound debug message')]
    public function testSessionManagerNotBound(): void
    {
        $this->channel->expects($this->once())
            ->method('log')
            ->with('debug', 'SessionManager not bound in container, skipping rebind', []);

        $this->logger->sessionManagerNotBound();
    }

    #[TestDox('logs session manager reset debug message')]
    public function testSessionManagerReset(): void
    {
        $this->channel->expects($this->once())
            ->method('log')
            ->with('debug', 'Reset session manager with current configuration', []);

        $this->logger->sessionManagerReset();
    }

    #[TestDox('logs session manager reset failure error message with exception details')]
    public function testSessionManagerResetFailed(): void
    {
        $exception = new \InvalidArgumentException('Invalid configuration');

        $this->channel->expects($this->once())
            ->method('log')
            ->with('error', "Failed to reset session manager: {$exception->getMessage()}", [
                'tenant_session.exception' => \InvalidArgumentException::class,
                'tenant_session.file' => $exception->getFile(),
                'tenant_session.line' => $exception->getLine(),
            ]);

        $this->logger->sessionManagerResetFailed($exception);
    }

    #[TestDox('logs session manager not bound during reset debug message')]
    public function testSessionManagerNotBoundDuringReset(): void
    {
        $this->channel->expects($this->once())
            ->method('log')
            ->with('debug', 'No SessionManager bound in container during reset', []);

        $this->logger->sessionManagerNotBoundDuringReset();
    }

    #[TestDox('extends BaseLogger')]
    public function testExtendsBaseLogger(): void
    {
        $this->assertInstanceOf(\Modules\Core\Logs\BaseLogger::class, $this->logger);
    }

    #[TestDox('has correct context prefix')]
    public function testHasCorrectContextPrefix(): void
    {
        // Use reflection to check the protected property
        $reflection = new \ReflectionClass($this->logger);
        $property = $reflection->getProperty('contextPrefix');
        $property->setAccessible(true);

        $this->assertEquals('tenant_session', $property->getValue($this->logger));
    }
}
