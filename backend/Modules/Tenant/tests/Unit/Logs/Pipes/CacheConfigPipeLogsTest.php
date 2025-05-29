<?php

namespace Modules\Tenant\Tests\Unit\Logs\Pipes;

use Illuminate\Log\LogManager;
use Modules\Tenant\Logs\Pipes\CacheConfigPipeLogs;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @testdox CacheConfigPipeLogs
 */
#[CoversClass(CacheConfigPipeLogs::class)]
#[Group('tenant')]
#[Group('unit')]
#[Group('logs')]
class CacheConfigPipeLogsTest extends TestCase
{
    private LogManager&MockObject $logManager;
    private LoggerInterface&MockObject $channel;
    private CacheConfigPipeLogs $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logManager = $this->createMock(LogManager::class);
        $this->channel = $this->createMock(LoggerInterface::class);

        $this->logManager->expects($this->any())
            ->method('channel')
            ->with('stack')
            ->willReturn($this->channel);

        $this->logger = new CacheConfigPipeLogs($this->logManager);
    }

    #[TestDox('logs cache manager rebound debug message')]
    public function testCacheManagerRebound(): void
    {
        $this->channel->expects($this->once())
            ->method('log')
            ->with('debug', 'Rebound cache manager with new configuration', []);

        $this->logger->cacheManagerRebound();
    }

    #[TestDox('logs rebind failure error message')]
    public function testRebindFailed(): void
    {
        $errorMessage = 'Connection refused';

        $this->channel->expects($this->once())
            ->method('log')
            ->with('error', "Failed to rebind cache manager: {$errorMessage}", []);

        $this->logger->rebindFailed($errorMessage);
    }

    #[TestDox('logs cache manager reset debug message')]
    public function testCacheManagerReset(): void
    {
        $this->channel->expects($this->once())
            ->method('log')
            ->with('debug', 'Reset cache manager to original configuration', []);

        $this->logger->cacheManagerReset();
    }

    #[TestDox('logs reset failure error message')]
    public function testResetFailed(): void
    {
        $errorMessage = 'Invalid configuration';

        $this->channel->expects($this->once())
            ->method('log')
            ->with('error', "Failed to reset cache manager: {$errorMessage}", []);

        $this->logger->resetFailed($errorMessage);
    }

    #[TestDox('extends BaseLogger')]
    public function testExtendsBaseLogger(): void
    {
        $this->assertInstanceOf(\Modules\Core\Logs\BaseLogger::class, $this->logger);
    }
}