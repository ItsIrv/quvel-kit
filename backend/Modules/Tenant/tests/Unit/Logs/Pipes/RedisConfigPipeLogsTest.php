<?php

namespace Modules\Tenant\Tests\Unit\Logs\Pipes;

use Illuminate\Log\LogManager;
use Modules\Tenant\Logs\Pipes\RedisConfigPipeLogs;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @testdox RedisConfigPipeLogs
 */
#[CoversClass(RedisConfigPipeLogs::class)]
#[Group('tenant')]
#[Group('unit')]
#[Group('logs')]
class RedisConfigPipeLogsTest extends TestCase
{
    private LogManager&MockObject $logManager;
    private LoggerInterface&MockObject $channel;
    private RedisConfigPipeLogs $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logManager = $this->createMock(LogManager::class);
        $this->channel = $this->createMock(LoggerInterface::class);

        $this->logManager->expects($this->any())
            ->method('channel')
            ->with('stack')
            ->willReturn($this->channel);

        $this->logger = new RedisConfigPipeLogs($this->logManager);
    }

    #[TestDox('logs connections refreshed debug message')]
    public function testConnectionsRefreshed(): void
    {
        $this->channel->expects($this->once())
            ->method('log')
            ->with('debug', 'Refreshed Redis connections with new configuration', []);

        $this->logger->connectionsRefreshed();
    }

    #[TestDox('logs connections failure error message')]
    public function testConnectionsFailed(): void
    {
        $errorMessage = 'Redis connection timeout';

        $this->channel->expects($this->once())
            ->method('log')
            ->with('error', "Failed to refresh Redis connections: {$errorMessage}", []);

        $this->logger->connectionsFailed($errorMessage);
    }

    #[TestDox('logs connections reset debug message')]
    public function testConnectionsReset(): void
    {
        $this->channel->expects($this->once())
            ->method('log')
            ->with('debug', 'Reset Redis connections with current configuration', []);

        $this->logger->connectionsReset();
    }

    #[TestDox('logs reset failure error message')]
    public function testResetFailed(): void
    {
        $errorMessage = 'Unable to reset Redis';

        $this->channel->expects($this->once())
            ->method('log')
            ->with('error', "Failed to reset Redis connections: {$errorMessage}", []);

        $this->logger->resetFailed($errorMessage);
    }

    #[TestDox('extends BaseLogger')]
    public function testExtendsBaseLogger(): void
    {
        $this->assertInstanceOf(\Modules\Core\Logs\BaseLogger::class, $this->logger);
    }
}