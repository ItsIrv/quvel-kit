<?php

namespace Modules\Core\Tests\Unit\Services\Security;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\Request;
use Mockery;
use Mockery\MockInterface;
use Modules\Core\Enums\CoreHeader;
use Modules\Core\Services\Security\RequestPrivacy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

#[CoversClass(RequestPrivacy::class)]
#[Group('core-module')]
#[Group('core-services')]
final class RequestPrivacyTest extends TestCase
{
    /**
     * @var Request|MockInterface
     */
    protected Request $request;

    /**
     * @var ConfigRepository|MockInterface
     */
    protected ConfigRepository $config;

    /**
     * @var RequestPrivacy
     */
    protected RequestPrivacy $requestPrivacy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = Mockery::mock(Request::class);
        $this->config  = Mockery::mock(ConfigRepository::class);
    }

    #[TestDox('It should consider request internal when both IP and API key checks pass')]
    public function testConsidersRequestInternalWhenBothChecksPass(): void
    {
        // Arrange
        $ip         = '192.168.1.1';
        $apiKey     = 'valid-api-key';
        $trustedIps = [$ip, '10.0.0.1'];

        $this->request->shouldReceive('ip')
            ->once()
            ->andReturn($ip);

        $this->request->shouldReceive('header')
            ->once()
            ->with(CoreHeader::SSR_KEY->value)
            ->andReturn($apiKey);

        // Mock config calls
        $this->config->shouldReceive('get')
            ->with('core.privacy.disable_ip_check')
            ->andReturn(false);

        $this->config->shouldReceive('get')
            ->with('core.privacy.trusted_ips')
            ->andReturn($trustedIps);

        $this->config->shouldReceive('get')
            ->with('core.privacy.disable_key_check')
            ->andReturn(false);

        $this->config->shouldReceive('get')
            ->with('core.privacy.ssr_api_key')
            ->andReturn($apiKey);

        // Act
        $this->requestPrivacy = new RequestPrivacy($this->request, $this->config);
        $result               = $this->requestPrivacy->isInternalRequest();

        // Assert
        $this->assertTrue($result);
    }

    #[TestDox('It should consider request internal when IP check is disabled')]
    public function testConsidersRequestInternalWhenIpCheckDisabled(): void
    {
        // Arrange
        $apiKey = 'valid-api-key';

        $this->request->shouldReceive('ip')
            ->never();

        $this->request->shouldReceive('header')
            ->once()
            ->with(CoreHeader::SSR_KEY->value)
            ->andReturn($apiKey);

        // Mock config calls
        $this->config->shouldReceive('get')
            ->with('core.privacy.disable_ip_check')
            ->andReturn(true);

        $this->config->shouldReceive('get')
            ->with('core.privacy.disable_key_check')
            ->andReturn(false);

        $this->config->shouldReceive('get')
            ->with('core.privacy.ssr_api_key')
            ->andReturn($apiKey);

        // Act
        $this->requestPrivacy = new RequestPrivacy($this->request, $this->config);
        $result               = $this->requestPrivacy->isInternalRequest();

        // Assert
        $this->assertTrue($result);
    }

    #[TestDox('It should consider request internal when API key check is disabled')]
    public function testConsidersRequestInternalWhenApiKeyCheckDisabled(): void
    {
        // Arrange
        $ip         = '192.168.1.1';
        $trustedIps = [$ip, '10.0.0.1'];

        $this->request->shouldReceive('ip')
            ->once()
            ->andReturn($ip);

        $this->request->shouldReceive('header')
            ->never();

        // Mock config calls
        $this->config->shouldReceive('get')
            ->with('core.privacy.disable_ip_check')
            ->andReturn(false);

        $this->config->shouldReceive('get')
            ->with('core.privacy.trusted_ips')
            ->andReturn($trustedIps);

        $this->config->shouldReceive('get')
            ->with('core.privacy.disable_key_check')
            ->andReturn(true);

        // Act
        $this->requestPrivacy = new RequestPrivacy($this->request, $this->config);
        $result               = $this->requestPrivacy->isInternalRequest();

        // Assert
        $this->assertTrue($result);
    }

    #[TestDox('It should consider request external when IP is not trusted')]
    public function testConsidersRequestExternalWhenIpNotTrusted(): void
    {
        // Arrange
        $ip         = '192.168.1.1';
        $apiKey     = 'valid-api-key';
        $trustedIps = ['10.0.0.1', '172.16.0.1'];

        $this->request->shouldReceive('ip')
            ->once()
            ->andReturn($ip);

        $this->request->shouldReceive('header')
            ->never()
            ->with(CoreHeader::SSR_KEY->value)
            ->andReturn($apiKey);

        // Mock config calls
        $this->config->shouldReceive('get')
            ->with('core.privacy.disable_ip_check')
            ->andReturn(false);

        $this->config->shouldReceive('get')
            ->with('core.privacy.trusted_ips')
            ->andReturn($trustedIps);

        $this->config->shouldReceive('get')
            ->with('core.privacy.disable_key_check')
            ->andReturn(false);

        $this->config->shouldReceive('get')
            ->with('core.privacy.ssr_api_key')
            ->andReturn($apiKey);

        // Act
        $this->requestPrivacy = new RequestPrivacy($this->request, $this->config);
        $result               = $this->requestPrivacy->isInternalRequest();

        // Assert
        $this->assertFalse($result);
    }

    #[TestDox('It should consider request external when API key is invalid')]
    public function testConsidersRequestExternalWhenApiKeyInvalid(): void
    {
        // Arrange
        $ip          = '192.168.1.1';
        $apiKey      = 'invalid-api-key';
        $validApiKey = 'valid-api-key';
        $trustedIps  = [$ip, '10.0.0.1'];

        $this->request->shouldReceive('ip')
            ->once()
            ->andReturn($ip);

        $this->request->shouldReceive('header')
            ->once()
            ->with(CoreHeader::SSR_KEY->value)
            ->andReturn($apiKey);

        // Mock config calls
        $this->config->shouldReceive('get')
            ->with('core.privacy.disable_ip_check')
            ->andReturn(false);

        $this->config->shouldReceive('get')
            ->with('core.privacy.trusted_ips')
            ->andReturn($trustedIps);

        $this->config->shouldReceive('get')
            ->with('core.privacy.disable_key_check')
            ->andReturn(false);

        $this->config->shouldReceive('get')
            ->with('core.privacy.ssr_api_key')
            ->andReturn($validApiKey);

        // Act
        $this->requestPrivacy = new RequestPrivacy($this->request, $this->config);
        $result               = $this->requestPrivacy->isInternalRequest();

        // Assert
        $this->assertFalse($result);
    }

    #[TestDox('It should consider request internal when both checks are disabled')]
    public function testConsidersRequestInternalWhenBothChecksDisabled(): void
    {
        // Arrange
        // No need to mock request methods as they shouldn't be called

        // Mock config calls
        $this->config->shouldReceive('get')
            ->with('core.privacy.disable_ip_check')
            ->andReturn(true);

        $this->config->shouldReceive('get')
            ->with('core.privacy.disable_key_check')
            ->andReturn(true);

        // Act
        $this->requestPrivacy = new RequestPrivacy($this->request, $this->config);
        $result               = $this->requestPrivacy->isInternalRequest();

        // Assert
        $this->assertTrue($result);
    }
}
