<?php

namespace Modules\Core\Tests\Unit\Logs;

use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Context;
use Modules\Core\Logs\BaseLogger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

/**
 * @testdox BaseLogger
 */
#[CoversClass(BaseLogger::class)]
#[Group('core-module')]
#[Group('core-logs')]
class BaseLoggerTest extends TestCase
{
    private LogManager $logManager;
    private BaseLogger $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logManager = $this->createMock(LogManager::class);

        // Create a concrete implementation for testing
        $this->logger = new class ($this->logManager) extends BaseLogger {
            protected string $channel = 'test';
            protected string $contextPrefix = 'test_prefix';
        };
    }

    #[TestDox('implements PSR-3 LoggerInterface')]
    public function testImplementsLoggerInterface(): void
    {
        $this->assertInstanceOf(LoggerInterface::class, $this->logger);
    }

    #[TestDox('logs emergency messages')]
    public function testLogsEmergencyMessages(): void
    {
        $message = 'Emergency message';
        $context = ['key' => 'value'];

        $mockChannel = $this->createMock(LoggerInterface::class);
        $mockChannel->expects($this->once())
            ->method('log')
            ->with('emergency', $message, $context);

        $this->logManager->expects($this->once())
            ->method('channel')
            ->with('test')
            ->willReturn($mockChannel);

        $this->logger->emergency($message, $context);
    }

    #[TestDox('logs alert messages')]
    public function testLogsAlertMessages(): void
    {
        $message = 'Alert message';
        $context = ['key' => 'value'];

        $mockChannel = $this->createMock(LoggerInterface::class);
        $mockChannel->expects($this->once())
            ->method('log')
            ->with('alert', $message, $context);

        $this->logManager->expects($this->once())
            ->method('channel')
            ->with('test')
            ->willReturn($mockChannel);

        $this->logger->alert($message, $context);
    }

    #[TestDox('logs critical messages')]
    public function testLogsCriticalMessages(): void
    {
        $message = 'Critical message';
        $context = ['key' => 'value'];

        $mockChannel = $this->createMock(LoggerInterface::class);
        $mockChannel->expects($this->once())
            ->method('log')
            ->with('critical', $message, $context);

        $this->logManager->expects($this->once())
            ->method('channel')
            ->with('test')
            ->willReturn($mockChannel);

        $this->logger->critical($message, $context);
    }

    #[TestDox('logs error messages')]
    public function testLogsErrorMessages(): void
    {
        $message = 'Error message';
        $context = ['key' => 'value'];

        $mockChannel = $this->createMock(LoggerInterface::class);
        $mockChannel->expects($this->once())
            ->method('log')
            ->with('error', $message, $context);

        $this->logManager->expects($this->once())
            ->method('channel')
            ->with('test')
            ->willReturn($mockChannel);

        $this->logger->error($message, $context);
    }

    #[TestDox('logs warning messages')]
    public function testLogsWarningMessages(): void
    {
        $message = 'Warning message';
        $context = ['key' => 'value'];

        $mockChannel = $this->createMock(LoggerInterface::class);
        $mockChannel->expects($this->once())
            ->method('log')
            ->with('warning', $message, $context);

        $this->logManager->expects($this->once())
            ->method('channel')
            ->with('test')
            ->willReturn($mockChannel);

        $this->logger->warning($message, $context);
    }

    #[TestDox('logs notice messages')]
    public function testLogsNoticeMessages(): void
    {
        $message = 'Notice message';
        $context = ['key' => 'value'];

        $mockChannel = $this->createMock(LoggerInterface::class);
        $mockChannel->expects($this->once())
            ->method('log')
            ->with('notice', $message, $context);

        $this->logManager->expects($this->once())
            ->method('channel')
            ->with('test')
            ->willReturn($mockChannel);

        $this->logger->notice($message, $context);
    }

    #[TestDox('logs info messages')]
    public function testLogsInfoMessages(): void
    {
        $message = 'Info message';
        $context = ['key' => 'value'];

        $mockChannel = $this->createMock(LoggerInterface::class);
        $mockChannel->expects($this->once())
            ->method('log')
            ->with('info', $message, $context);

        $this->logManager->expects($this->once())
            ->method('channel')
            ->with('test')
            ->willReturn($mockChannel);

        $this->logger->info($message, $context);
    }

    #[TestDox('logs debug messages')]
    public function testLogsDebugMessages(): void
    {
        $message = 'Debug message';
        $context = ['key' => 'value'];

        $mockChannel = $this->createMock(LoggerInterface::class);
        $mockChannel->expects($this->once())
            ->method('log')
            ->with('debug', $message, $context);

        $this->logManager->expects($this->once())
            ->method('channel')
            ->with('test')
            ->willReturn($mockChannel);

        $this->logger->debug($message, $context);
    }

    #[TestDox('logs with arbitrary level')]
    public function testLogsWithArbitraryLevel(): void
    {
        $level   = 'custom';
        $message = 'Custom level message';
        $context = ['key' => 'value'];

        $mockChannel = $this->createMock(LoggerInterface::class);
        $mockChannel->expects($this->once())
            ->method('log')
            ->with($level, $message, $context);

        $this->logManager->expects($this->once())
            ->method('channel')
            ->with('test')
            ->willReturn($mockChannel);

        $this->logger->log($level, $message, $context);
    }

    #[TestDox('accepts stringable messages')]
    public function testAcceptsStringableMessages(): void
    {
        $stringable = new class () {
            public function __toString(): string
            {
                return 'Stringable message';
            }
        };

        $mockChannel = $this->createMock(LoggerInterface::class);
        $mockChannel->expects($this->once())
            ->method('log')
            ->with('info', $stringable, []);

        $this->logManager->expects($this->once())
            ->method('channel')
            ->with('test')
            ->willReturn($mockChannel);

        $this->logger->info($stringable);
    }

    #[TestDox('uses default channel when not overridden')]
    public function testUsesDefaultChannelWhenNotOverridden(): void
    {
        $defaultLogger = new class ($this->logManager) extends BaseLogger {
            // Uses default 'stack' channel
        };

        $mockChannel = $this->createMock(LoggerInterface::class);
        $mockChannel->expects($this->once())
            ->method('log')
            ->with('info', 'message', []);

        $this->logManager->expects($this->once())
            ->method('channel')
            ->with('stack')
            ->willReturn($mockChannel);

        $defaultLogger->info('message');
    }

    #[TestDox('enriches context with trace ID when available')]
    public function testEnrichesContextWithTraceIdWhenAvailable(): void
    {
        Context::add('trace_id', 'test-trace-123');

        $loggerWithEnrichment = new class ($this->logManager) extends BaseLogger {
            protected string $channel = 'test';

            // Override log method to test enrichContext
            public function log(mixed $level, string|\Stringable $message, array $context = []): void
            {
                $context = $this->enrichContext($context);
                parent::log($level, $message, $context);
            }
        };

        $mockChannel = $this->createMock(LoggerInterface::class);
        $mockChannel->expects($this->once())
            ->method('log')
            ->with('info', 'message', $this->callback(function ($context) {
                return isset($context['trace_id']) && $context['trace_id'] === 'test-trace-123';
            }));

        $this->logManager->expects($this->once())
            ->method('channel')
            ->with('test')
            ->willReturn($mockChannel);

        $loggerWithEnrichment->info('message', ['key' => 'value']);

        Context::flush();
    }

    #[TestDox('adds prefix to context keys')]
    public function testAddsPrefixToContextKeys(): void
    {
        $loggerWithPrefix = new class ($this->logManager) extends BaseLogger {
            protected string $channel = 'test';
            protected string $contextPrefix = 'app.module';

            // Override log method to test enrichContext
            public function log(mixed $level, string|\Stringable $message, array $context = []): void
            {
                $context = $this->enrichContext($context);
                parent::log($level, $message, $context);
            }
        };

        $mockChannel = $this->createMock(LoggerInterface::class);
        $mockChannel->expects($this->once())
            ->method('log')
            ->with('info', 'message', $this->callback(function ($context) {
                return isset($context['app.module.key1']) && $context['app.module.key1'] === 'value1' &&
                    isset($context['app.module.key2']) && $context['app.module.key2'] === 'value2';
            }));

        $this->logManager->expects($this->once())
            ->method('channel')
            ->with('test')
            ->willReturn($mockChannel);

        $loggerWithPrefix->info('message', ['key1' => 'value1', 'key2' => 'value2']);
    }

    #[TestDox('returns empty context unchanged with prefix')]
    public function testReturnsEmptyContextUnchangedWithPrefix(): void
    {
        $loggerWithPrefix = new class ($this->logManager) extends BaseLogger {
            protected string $channel = 'test';
            protected string $contextPrefix = 'prefix';

            // Override log method to test enrichContext
            public function log(mixed $level, string|\Stringable $message, array $context = []): void
            {
                $context = $this->enrichContext($context);
                parent::log($level, $message, $context);
            }
        };

        $mockChannel = $this->createMock(LoggerInterface::class);
        $mockChannel->expects($this->once())
            ->method('log')
            ->with('info', 'message', []);

        $this->logManager->expects($this->once())
            ->method('channel')
            ->with('test')
            ->willReturn($mockChannel);

        $loggerWithPrefix->info('message', []);
    }

    #[TestDox('returns context unchanged without prefix')]
    public function testReturnsContextUnchangedWithoutPrefix(): void
    {
        $loggerWithoutPrefix = new class ($this->logManager) extends BaseLogger {
            protected string $channel = 'test';
            protected string $contextPrefix = '';

            // Override log method to test enrichContext
            public function log(mixed $level, string|\Stringable $message, array $context = []): void
            {
                $context = $this->enrichContext($context);
                parent::log($level, $message, $context);
            }
        };

        $originalContext = ['key' => 'value', 'nested' => ['data' => 123]];

        $mockChannel = $this->createMock(LoggerInterface::class);
        $mockChannel->expects($this->once())
            ->method('log')
            ->with('info', 'message', $originalContext);

        $this->logManager->expects($this->once())
            ->method('channel')
            ->with('test')
            ->willReturn($mockChannel);

        $loggerWithoutPrefix->info('message', $originalContext);
    }
}
