<?php

namespace Modules\Tenant\Tests\Unit\Logs\Pipes;

use Illuminate\Log\LogManager;
use Modules\Tenant\Logs\Pipes\DatabaseConfigPipeLogs;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @testdox DatabaseConfigPipeLogs
 */
#[CoversClass(DatabaseConfigPipeLogs::class)]
#[Group('tenant')]
#[Group('unit')]
#[Group('logs')]
class DatabaseConfigPipeLogsTest extends TestCase
{
    private LogManager&MockObject $logManager;
    private LoggerInterface&MockObject $channel;
    private DatabaseConfigPipeLogs $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logManager = $this->createMock(LogManager::class);
        $this->channel = $this->createMock(LoggerInterface::class);

        $this->logManager->expects($this->any())
            ->method('channel')
            ->with('stack')
            ->willReturn($this->channel);

        $this->logger = new DatabaseConfigPipeLogs($this->logManager);
    }

    #[TestDox('logs connection switched debug message')]
    public function testConnectionSwitched(): void
    {
        $connection = 'tenant_db';
        $tenantName = 'Acme Corp';

        $this->channel->expects($this->once())
            ->method('log')
            ->with('debug', "Switched database connection to {$connection} for tenant {$tenantName}", []);

        $this->logger->connectionSwitched($connection, $tenantName);
    }

    #[TestDox('logs connection reset debug message')]
    public function testConnectionReset(): void
    {
        $connection = 'default';

        $this->channel->expects($this->once())
            ->method('log')
            ->with('debug', "Reset database connection to original: {$connection}", []);

        $this->logger->connectionReset($connection);
    }

    #[TestDox('logs reset failure error message')]
    public function testResetFailed(): void
    {
        $errorMessage = 'Database connection error';

        $this->channel->expects($this->once())
            ->method('log')
            ->with('error', "Failed to reset database connection: {$errorMessage}", []);

        $this->logger->resetFailed($errorMessage);
    }

    #[TestDox('extends BaseLogger')]
    public function testExtendsBaseLogger(): void
    {
        $this->assertInstanceOf(\Modules\Core\Logs\BaseLogger::class, $this->logger);
    }
}