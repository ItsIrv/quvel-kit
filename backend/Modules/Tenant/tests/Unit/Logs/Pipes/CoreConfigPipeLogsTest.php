<?php

namespace Modules\Tenant\Tests\Unit\Logs\Pipes;

use Illuminate\Log\LogManager;
use Modules\Tenant\Logs\Pipes\CoreConfigPipeLogs;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @testdox CoreConfigPipeLogs
 */
#[CoversClass(CoreConfigPipeLogs::class)]
#[Group('tenant')]
#[Group('unit')]
#[Group('logs')]
class CoreConfigPipeLogsTest extends TestCase
{
    private LogManager&MockObject $logManager;
    private LoggerInterface&MockObject $channel;
    private CoreConfigPipeLogs $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logManager = $this->createMock(LogManager::class);
        $this->channel = $this->createMock(LoggerInterface::class);

        $this->logManager->expects($this->any())
            ->method('channel')
            ->with('stack')
            ->willReturn($this->channel);

        $this->logger = new CoreConfigPipeLogs($this->logManager);
    }

    #[TestDox('logs URL generator updated debug message')]
    public function testUrlGeneratorUpdated(): void
    {
        $appUrl = 'https://example.com';

        $this->channel->expects($this->once())
            ->method('log')
            ->with('debug', "Updated URL generator with new root URL: {$appUrl}", []);

        $this->logger->urlGeneratorUpdated($appUrl);
    }

    #[TestDox('logs URL generator failure error message')]
    public function testUrlGeneratorFailed(): void
    {
        $errorMessage = 'Invalid URL format';

        $this->channel->expects($this->once())
            ->method('log')
            ->with('error', "Failed to refresh URL generator: {$errorMessage}", []);

        $this->logger->urlGeneratorFailed($errorMessage);
    }

    #[TestDox('logs timezone updated debug message')]
    public function testTimezoneUpdated(): void
    {
        $timezone = 'America/New_York';

        $this->channel->expects($this->once())
            ->method('log')
            ->with('debug', "Updated application timezone to: {$timezone}", []);

        $this->logger->timezoneUpdated($timezone);
    }

    #[TestDox('logs timezone failure error message')]
    public function testTimezoneFailed(): void
    {
        $errorMessage = 'Invalid timezone';

        $this->channel->expects($this->once())
            ->method('log')
            ->with('error', "Failed to refresh timezone: {$errorMessage}", []);

        $this->logger->timezoneFailed($errorMessage);
    }

    #[TestDox('logs locale updated debug message')]
    public function testLocaleUpdated(): void
    {
        $locale = 'es_ES';

        $this->channel->expects($this->once())
            ->method('log')
            ->with('debug', "Updated application locale to: {$locale}", []);

        $this->logger->localeUpdated($locale);
    }

    #[TestDox('logs locale failure error message')]
    public function testLocaleFailed(): void
    {
        $errorMessage = 'Unsupported locale';

        $this->channel->expects($this->once())
            ->method('log')
            ->with('error', "Failed to refresh locale: {$errorMessage}", []);

        $this->logger->localeFailed($errorMessage);
    }

    #[TestDox('logs resources reset debug message')]
    public function testResourcesReset(): void
    {
        $this->channel->expects($this->once())
            ->method('log')
            ->with('debug', 'Reset core resources to original configuration', []);

        $this->logger->resourcesReset();
    }

    #[TestDox('logs resources reset failure error message')]
    public function testResourcesResetFailed(): void
    {
        $errorMessage = 'Unable to restore configuration';

        $this->channel->expects($this->once())
            ->method('log')
            ->with('error', "Failed to reset core resources: {$errorMessage}", []);

        $this->logger->resourcesResetFailed($errorMessage);
    }

    #[TestDox('logs forwarded prefix applied debug message')]
    public function testForwardedPrefixApplied(): void
    {
        $prefix = '/app';

        $this->channel->expects($this->once())
            ->method('log')
            ->with('debug', "Applied X-Forwarded-Prefix to URL generator: {$prefix}", []);

        $this->logger->forwardedPrefixApplied($prefix);
    }

    #[TestDox('logs forwarded prefix failure error message')]
    public function testForwardedPrefixFailed(): void
    {
        $errorMessage = 'Unable to apply prefix';

        $this->channel->expects($this->once())
            ->method('log')
            ->with('error', "Failed to apply X-Forwarded-Prefix: {$errorMessage}", []);

        $this->logger->forwardedPrefixFailed($errorMessage);
    }

    #[TestDox('extends BaseLogger')]
    public function testExtendsBaseLogger(): void
    {
        $this->assertInstanceOf(\Modules\Core\Logs\BaseLogger::class, $this->logger);
    }
}
