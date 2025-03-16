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
        $this->config = Mockery::mock(ConfigRepository::class);

        // Initialize the service with the mocked dependencies
        $this->service = new NonceSessionService($this->session, $this->config);
    }

    public function test_set_nonce_stores_nonce_and_timestamp(): void
    {
        $nonce = 'test_nonce';
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

    public function test_get_nonce_returns_nonce_if_valid(): void
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
            ->with('auth.oauth.nonce_ttl', 1)
            ->andReturn(60);

        // Act
        $result = $this->service->getNonce();

        // Assert
        $this->assertEquals($nonce, $result);
    }

    public function test_get_nonce_returns_null_if_invalid(): void
    {
        // Mock `isValid` to return `false`
        $this->session->shouldReceive('get')
            ->with('auth.nonce')
            ->andReturn('test_nonce');

        $this->session->shouldReceive('get')
            ->with('auth.nonce.timestamp')
            ->andReturn(Carbon::now()->subSeconds(120)); // Expired timestamp

        $this->config->shouldReceive('get')
            ->with('auth.oauth.nonce_ttl', 1)
            ->andReturn(60);

        $this->session->shouldReceive('forget')->once()->with('auth.nonce');
        $this->session->shouldReceive('forget')->once()->with('auth.nonce.timestamp');

        // Act
        $result = $this->service->getNonce();

        // Assert
        $this->assertNull($result);
    }

    public function test_is_valid_returns_true_if_nonce_is_within_ttl(): void
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
            ->with('auth.oauth.nonce_ttl', 1)
            ->andReturn(60); // TTL is 60 seconds

        // Act
        $result = $this->service->isValid();

        // Assert
        $this->assertTrue($result);
    }

    public function test_is_valid_returns_false_if_nonce_or_timestamp_does_not_exist(): void
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

    public function test_is_valid_returns_false_if_nonce_is_expired(): void
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
            ->with('auth.oauth.nonce_ttl', 1)
            ->andReturn(60); // TTL is 60 seconds

        // Act
        $result = $this->service->isValid();

        // Assert
        $this->assertFalse($result);
    }

    public function test_clear_removes_nonce_and_timestamp(): void
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
