<?php

namespace Modules\Auth\Tests\Unit\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Mockery;
use Mockery\MockInterface;
use Modules\Auth\Enums\OAuthStatusEnum;
use Modules\Auth\Exceptions\OAuthException;
use Modules\Auth\Services\HmacService;
use Modules\Auth\Services\ServerTokenService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Psr\SimpleCache\InvalidArgumentException;
use Random\RandomException;
use Tests\TestCase;

#[CoversClass(ServerTokenService::class)]
#[Group('auth-module')]
#[Group('auth-services')]
class ServerTokenServiceTest extends TestCase
{
    private CacheRepository|MockInterface $cache;
    private ConfigRepository|MockInterface $config;
    private HmacService|MockInterface $hmacService;
    private ServerTokenService $service;

    public function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->cache = Mockery::mock(CacheRepository::class);
        $this->config = Mockery::mock(ConfigRepository::class);
        $this->hmacService = Mockery::mock(HmacService::class);

        // Initialize the service
        $this->service = new ServerTokenService(
            $this->cache,
            $this->config,
            $this->hmacService
        );
    }

    /**
     * @throws RandomException
     */
    public function testCreateGeneratesSecureTokenAndStoresItInCache(): void
    {
        $nonce = 'test_nonce';
        $serverToken = 'secure_random_token';
        $ttl = 60; // Token TTL
        $signedToken = 'signed_server_token';

        // Mock random token generation
        $this->service = Mockery::mock(ServerTokenService::class, [
            $this->cache,
            $this->config,
            $this->hmacService,
        ])->makePartial();

        $this->service->shouldAllowMockingProtectedMethods();
        $this->service->shouldReceive('generateRandomToken')
            ->once()
            ->andReturn($serverToken);

        // Mock config retrieval for TTL
        $this->config->shouldReceive('get')
            ->with('auth.oauth.token_ttl', 1)
            ->andReturn($ttl);

        // Mock cache storage
        $this->cache->shouldReceive('put')
            ->once()
            ->with(
                'server_token_' . $serverToken,
                $nonce,
                $ttl
            );

        // Mock HMAC signing
        $this->hmacService->shouldReceive('signWithHmac')
            ->with($serverToken)
            ->once()
            ->andReturn($signedToken);

        // Act
        $result = $this->service->create($nonce);

        // Assert
        $this->assertEquals($signedToken, $result);
    }

    /**
     * @throws RandomException
     */
    public function testGenerateRandomTokenProducesSecureRandomString(): void
    {
        // Act
        $result = $this->service->generateRandomToken();

        // Assert
        $this->assertNotEmpty($result);
        $this->assertEquals(128, strlen($result)); // 64 bytes = 128 hex characters
    }

    public function testGetSignedTokenReturnsSignedServerToken(): void
    {
        $serverToken = 'test_server_token';
        $signedToken = 'test_signed_token';

        // Mock HMAC signing
        $this->hmacService->shouldReceive('signWithHmac')
            ->with($serverToken)
            ->once()
            ->andReturn($signedToken);

        // Act
        $result = $this->service->getSignedToken($serverToken);

        // Assert
        $this->assertEquals($signedToken, $result);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testGetClientNonceRetrievesAssociatedNonce(): void
    {
        $signedServerToken = 'signed_token';
        $serverToken = 'plain_server_token';
        $nonce = 'test_nonce';

        // Mock extracting server token
        $this->hmacService->shouldReceive('extractAndVerify')
            ->with($signedServerToken)
            ->once()
            ->andReturn($serverToken);

        // Mock cache retrieval
        $this->cache->shouldReceive('get')
            ->with('server_token_' . $serverToken)
            ->once()
            ->andReturn($nonce);

        // Act
        $result = $this->service->getClientNonce($signedServerToken);

        // Assert
        $this->assertEquals($nonce, $result);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testGetClientNonceReturnsNullForInvalidToken(): void
    {
        $signedServerToken = 'invalid_signed_token';

        // Mock invalid server token
        $this->hmacService->shouldReceive('extractAndVerify')
            ->with($signedServerToken)
            ->once()
            ->andReturnNull();

        // Act
        $result = $this->service->getClientNonce($signedServerToken);

        // Assert
        $this->assertNull($result);
    }

    /**
     * @throws OAuthException
     */
    public function testForgetRemovesTokenFromCache(): void
    {
        $signedServerToken = 'signed_token';
        $serverToken = 'plain_server_token';

        // Mock extracting server token
        $this->hmacService->shouldReceive('extractAndVerify')
            ->with($signedServerToken)
            ->once()
            ->andReturn($serverToken);

        // Mock cache forget
        $this->cache->shouldReceive('forget')
            ->with('server_token_' . $serverToken)
            ->once()
            ->andReturnTrue();

        // Act
        $result = $this->service->forget($signedServerToken);

        // Assert
        $this->assertTrue($result);
    }

    public function testForgetThrowsExceptionForInvalidToken(): void
    {
        $signedServerToken = 'invalid_signed_token';

        // Mock invalid server token
        $this->hmacService->shouldReceive('extractAndVerify')
            ->with($signedServerToken)
            ->once()
            ->andReturnNull();

        // Expect exception
        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage(OAuthStatusEnum::INVALID_TOKEN->value);

        // Act
        $this->service->forget($signedServerToken);
    }
}
