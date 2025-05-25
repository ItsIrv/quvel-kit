<?php

namespace Modules\Auth\Tests\Unit\Services;

use Carbon\Carbon;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Session\Session;
use Mockery;
use Mockery\MockInterface;
use Modules\Auth\Services\NonceSessionService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(NonceSessionService::class)]
#[Group('auth-module')]
#[Group('auth-services')]
class NonceSessionServiceTest extends TestCase
{
    private Session|MockInterface $session;

    private ConfigRepository|MockInterface $config;

    private NonceSessionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->session = Mockery::mock(Session::class);
        $this->config  = Mockery::mock(ConfigRepository::class);

        // Initialize the service with the mocked dependencies
        $this->service = new NonceSessionService($this->session, $this->config);
    }

    public function testSetNonceStoresNonceAndTimestamp(): void
    {
        $nonce     = 'test_nonce';
        $timestamp = Carbon::now();

        // Mock session `put` calls
        $this->session->shouldReceive('put')
            ->once()
            ->with('auth.nonce', $nonce);

        $this->session->shouldReceive('put')
            ->once()
            ->with('auth.nonce.timestamp', Mockery::on(static function ($value) use ($timestamp) {
                return Carbon::parse($value)->diffInSeconds($timestamp) < 1;
            }));

        // Act
        $this->service->setNonce($nonce);
    }

    public function testGetNonceReturnsNonceIfValid(): void
    {
        $nonce = 'test_nonce';

        // Mock `isValid` to return `true`
        $this->session->shouldReceive('get')
            ->with('auth.nonce')
            ->andReturn($nonce);

        $this->session->shouldReceive('get')
            ->with('auth.nonce.timestamp')
            ->andReturn(Carbon::now()->subSeconds(10)); // Return valid previous timestamp

        $this->config->shouldReceive('get')
            ->with('auth.socialite.nonce_ttl', 1)
            ->andReturn(60);

        // Act
        $result = $this->service->getNonce();

        // Assert
        $this->assertEquals($nonce, $result);
    }

    public function testGetNonceReturnsNullIfInvalid(): void
    {
        // Mock `isValid` to return `false`
        $this->session->shouldReceive('get')
            ->with('auth.nonce')
            ->andReturn('test_nonce');

        $this->session->shouldReceive('get')
            ->with('auth.nonce.timestamp')
            ->andReturn(Carbon::now()->subSeconds(120)); // Expired timestamp

        $this->config->shouldReceive('get')
            ->with('auth.socialite.nonce_ttl', 1)
            ->andReturn(60);

        $this->session->shouldReceive('forget')->once()->with('auth.nonce');
        $this->session->shouldReceive('forget')->once()->with('auth.nonce.timestamp');

        // Act
        $result = $this->service->getNonce();

        // Assert
        $this->assertNull($result);
    }

    public function testIsValidReturnsTrueIfNonceIsWithinTtl(): void
    {
        // Mock valid nonce and timestamp
        $timestamp = Carbon::now()->subSeconds(30); // 30 seconds ago
        $this->session->shouldReceive('get')
            ->with('auth.nonce')
            ->andReturn('test_nonce');

        $this->session->shouldReceive('get')
            ->with('auth.nonce.timestamp')
            ->andReturn($timestamp);

        $this->config->shouldReceive('get')
            ->with('auth.socialite.nonce_ttl', 1)
            ->andReturn(60); // TTL is 60 seconds

        // Act
        $result = $this->service->isValid();

        // Assert
        $this->assertTrue($result);
    }

    public function testIsValidReturnsFalseIfNonceOrTimestampDoesNotExist(): void
    {
        // Case 1: `nonce` or `timestamp` is missing
        $this->session->shouldReceive('get')
            ->with('auth.nonce')
            ->andReturnNull();

        $this->session->shouldReceive('get')
            ->with('auth.nonce.timestamp')
            ->andReturnNull();

        // Act
        $result = $this->service->isValid();

        // Assert
        $this->assertFalse($result);
    }

    public function testIsValidReturnsFalseIfNonceIsExpired(): void
    {
        // Mock expired timestamp
        $timestamp = Carbon::now()->subSeconds(120); // 120 seconds ago
        $this->session->shouldReceive('get')
            ->with('auth.nonce')
            ->andReturn('test_nonce');

        $this->session->shouldReceive('get')
            ->with('auth.nonce.timestamp')
            ->andReturn($timestamp);

        $this->config->shouldReceive('get')
            ->with('auth.socialite.nonce_ttl', 1)
            ->andReturn(60); // TTL is 60 seconds

        // Act
        $result = $this->service->isValid();

        // Assert
        $this->assertFalse($result);
    }

    public function testClearRemovesNonceAndTimestamp(): void
    {
        // Mock session `forget` calls
        $this->session->shouldReceive('forget')
            ->once()
            ->with('auth.nonce');

        $this->session->shouldReceive('forget')
            ->once()
            ->with('auth.nonce.timestamp');

        // Act
        $this->service->clear();
    }
}
