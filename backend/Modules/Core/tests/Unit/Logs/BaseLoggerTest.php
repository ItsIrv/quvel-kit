<?php

namespace Modules\Core\Tests\Unit\Logs;

use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Context;
use Modules\Core\Logs\BaseLogger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

#[CoversClass(BaseLogger::class)]
class BaseLoggerTest extends TestCase
{
    private LogManager $logManager;
    private BaseLogger $logger;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->logManager = $this->createMock(LogManager::class);
        
        // Create a concrete implementation for testing
        $this->logger = new class($this->logManager) extends BaseLogger {
            protected string $channel = 'test';
            protected string $contextPrefix = 'test_prefix';
        };
    }

    #[Test]
    public function it_implements_logger_interface(): void
    {
        $this->assertInstanceOf(LoggerInterface::class, $this->logger);
    }

    #[Test]
    public function it_logs_emergency_messages(): void
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

    #[Test]
    public function it_logs_alert_messages(): void
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

    #[Test]
    public function it_logs_critical_messages(): void
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

    #[Test]
    public function it_logs_error_messages(): void
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

    #[Test]
    public function it_logs_warning_messages(): void
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

    #[Test]
    public function it_logs_notice_messages(): void
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

    #[Test]
    public function it_logs_info_messages(): void
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

    #[Test]
    public function it_logs_debug_messages(): void
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

    #[Test]
    public function it_logs_with_arbitrary_level(): void
    {
        $level = 'custom';
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

    #[Test]
    public function it_accepts_stringable_messages(): void
    {
        $stringable = new class {
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

    #[Test]
    public function it_uses_default_channel_when_not_overridden(): void
    {
        $defaultLogger = new class($this->logManager) extends BaseLogger {
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

    #[Test]
    public function enrich_context_adds_trace_id_when_available(): void
    {
        Context::add('trace_id', 'test-trace-123');
        
        $logger = new class($this->logManager) extends BaseLogger {
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
            
        $logger->info('message', ['key' => 'value']);
        
        Context::flush();
    }

    #[Test]
    public function enrich_context_adds_prefix_to_context_keys(): void
    {
        $logger = new class($this->logManager) extends BaseLogger {
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
            
        $logger->info('message', ['key1' => 'value1', 'key2' => 'value2']);
    }

    #[Test]
    public function enrich_context_returns_empty_context_unchanged_with_prefix(): void
    {
        $logger = new class($this->logManager) extends BaseLogger {
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
            
        $logger->info('message', []);
    }

    #[Test]
    public function enrich_context_returns_context_unchanged_without_prefix(): void
    {
        $logger = new class($this->logManager) extends BaseLogger {
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
            
        $logger->info('message', $originalContext);
    }
}