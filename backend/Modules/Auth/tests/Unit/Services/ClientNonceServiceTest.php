<?php

namespace Modules\Auth\Tests\Unit\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Mockery;
use Mockery\MockInterface;
use Modules\Auth\Enums\OAuthStatusEnum;
use Modules\Auth\Exceptions\OAuthException;
use Modules\Auth\Services\ClientNonceService;
use Modules\Auth\Services\HmacService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Psr\SimpleCache\InvalidArgumentException;
use Random\RandomException;
use Tests\TestCase;

#[CoversClass(ClientNonceService::class)]
#[Group('auth-module')]
#[Group('auth-services')]
class ClientNonceServiceTest extends TestCase
{
    private CacheRepository|MockInterface $cache;

    private ConfigRepository|MockInterface $config;

    private HmacService|MockInterface $hmacService;

    private ClientNonceService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = Mockery::mock(CacheRepository::class);
        $this->config = Mockery::mock(ConfigRepository::class);
        $this->hmacService = Mockery::mock(HmacService::class);

        $this->service = new ClientNonceService(
            $this->cache,
            $this->config,
            $this->hmacService
        );
    }

    /**
     * @throws OAuthException|InvalidArgumentException|RandomException
     */
    public function test_create_nonce_successfully(): void
    {
        $nonce = 'fixed_nonce_value';
        $signedNonce = 'signed_nonce';

        $serviceMock = Mockery::mock(ClientNonceService::class, [
            $this->cache,
            $this->config,
            $this->hmacService,
        ])->makePartial();

        $serviceMock->shouldAllowMockingProtectedMethods();
        $serviceMock->shouldReceive('generateRandomNonce')
            ->once()
            ->andReturn($nonce);

        $this->cache->shouldReceive('has')
            ->with('client_nonce_'.$nonce)
            ->once()
            ->andReturn(false);

        $this->cache->shouldReceive('put')
            ->once()
            ->with('client_nonce_'.$nonce, ClientNonceService::TOKEN_CREATED, 1);

        $this->config->shouldReceive('get')
            ->with('auth.oauth.nonce_ttl', 1)
            ->andReturn(1);

        $this->hmacService->shouldReceive('signWithHmac')
            ->with($nonce)
            ->once()
            ->andReturn($signedNonce);

        $result = $serviceMock->create();

        $this->assertEquals($signedNonce, $result);
    }

    /**
     * @throws OAuthException|InvalidArgumentException|RandomException
     */
    public function test_create_nonce_throws_exception_after_max_retries(): void
    {
        $this->cache->shouldReceive('has')
            ->times(1)
            ->andReturn(true);

        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage(OAuthStatusEnum::INVALID_NONCE->value);

        $this->service->create();
    }

    /**
     * Test retrieving a signed nonce.
     */
    public function test_get_signed_nonce(): void
    {
        $nonce = 'test_nonce';
        $signedNonce = 'signed_nonce';

        $this->hmacService->shouldReceive('signWithHmac')
            ->with($nonce)
            ->once()
            ->andReturn($signedNonce);

        $result = $this->service->getSignedNonce($nonce);

        $this->assertEquals($signedNonce, $result);
    }

    /**
     * @throws OAuthException|InvalidArgumentException
     */
    public function test_get_nonce_successfully(): void
    {
        $signedNonce = 'signed_nonce';
        $nonce = 'test_nonce';
        $expectedState = ClientNonceService::TOKEN_CREATED;

        $this->hmacService->shouldReceive('extractAndVerify')
            ->with($signedNonce)
            ->once()
            ->andReturn($nonce);

        $this->cache->shouldReceive('get')
            ->with('client_nonce_'.$nonce)
            ->once()
            ->andReturn($expectedState);

        $result = $this->service->getNonce($signedNonce, $expectedState);

        $this->assertEquals($nonce, $result);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function test_get_nonce_throws_exception(): void
    {
        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage(OAuthStatusEnum::INVALID_NONCE->value);

        $this->hmacService->shouldReceive('extractAndVerify')
            ->with('invalid_nonce')
            ->once()
            ->andReturn(null);

        $this->service->getNonce('invalid_nonce');
    }

    /**
     * @throws InvalidArgumentException
     */
    public function test_get_nonce_throws_exception_on_invalid_state(): void
    {
        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage(OAuthStatusEnum::INVALID_NONCE->value);

        $this->hmacService->shouldReceive('extractAndVerify')
            ->with('invalid_nonce')
            ->once()
            ->andReturn('valid_nonce');

        $this->cache->shouldReceive('get')
            ->with('client_nonce_valid_nonce')
            ->once()
            ->andReturn(null);

        $this->service->getNonce('invalid_nonce', -999);
    }

    public function test_assign_user_to_nonce(): void
    {
        $nonce = 'test_nonce';
        $userId = 123;

        $this->config->shouldReceive('get')
            ->with('auth.oauth.nonce_ttl', 1)
            ->andReturn(1);

        $this->cache->shouldReceive('put')
            ->with('client_nonce_'.$nonce, $userId, 1)
            ->once();

        $this->service->assignUserToNonce($nonce, $userId);
    }

    public function test_forget_nonce(): void
    {
        $nonce = 'test_nonce';

        $this->cache->shouldReceive('forget')
            ->with('client_nonce_'.$nonce)
            ->once()
            ->andReturn(true);

        $result = $this->service->forget($nonce);

        $this->assertTrue($result);
    }

    public function test_assign_redirected_to_nonce(): void
    {
        $nonce = 'test_nonce';

        $this->config->shouldReceive('get')
            ->with('auth.oauth.nonce_ttl', 1)
            ->andReturn(1);

        $this->cache->shouldReceive('put')
            ->once()
            ->with('client_nonce_'.$nonce, ClientNonceService::TOKEN_REDIRECTED, 1);

        $this->service->assignRedirectedToNonce($nonce);
    }

    /**
     * @throws OAuthException|InvalidArgumentException
     */
    public function test_get_user_id_from_nonce_successfully(): void
    {
        $nonce = 'test_nonce';
        $userId = 123;

        $this->cache->shouldReceive('get')
            ->with('client_nonce_'.$nonce)
            ->once()
            ->andReturn($userId);

        $result = $this->service->getUserIdFromNonce($nonce);

        $this->assertEquals($userId, $result);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function test_get_user_id_from_nonce_throws_exception_on_invalid_user_id(): void
    {
        $nonce = 'test_nonce';

        $this->cache->shouldReceive('get')
            ->with('client_nonce_'.$nonce)
            ->once()
            ->andReturn(ClientNonceService::TOKEN_CREATED);

        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage(OAuthStatusEnum::INVALID_NONCE->value);

        $this->service->getUserIdFromNonce($nonce);
    }
}
