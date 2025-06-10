<?php

namespace Modules\Auth\Tests\Unit\Pipes;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\Auth\Pipes\AuthConfigPipe;
use Modules\Tenant\Contracts\ConfigurationPipeInterface;
use Modules\Tenant\Models\Tenant;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @testdox AuthConfigPipe
 */
#[CoversClass(AuthConfigPipe::class)]
#[Group('auth-module')]
#[Group('auth-pipes')]
class AuthConfigPipeTest extends TestCase
{
    private AuthConfigPipe $pipe;
    private ConfigRepository&MockObject $config;
    private Tenant $tenantModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pipe = new AuthConfigPipe();
        $this->config = $this->createMock(ConfigRepository::class);
        $this->tenantModel = new Tenant();
        $this->tenantModel->id = 'test-tenant';
    }

    #[TestDox('implements ConfigurationPipeInterface')]
    public function testImplementsConfigurationPipeInterface(): void
    {
        $this->assertInstanceOf(ConfigurationPipeInterface::class, $this->pipe);
    }

    #[TestDox('returns correct priority of 50')]
    public function testReturnsCorrectPriority(): void
    {
        $this->assertEquals(50, $this->pipe->priority());
    }

    #[TestDox('returns all handled configuration keys')]
    public function testReturnsHandledConfigurationKeys(): void
    {
        $expectedKeys = [
            'socialite_providers',
            'socialite_nonce_ttl',
            'socialite_token_ttl',
            'hmac_secret_key',
            'oauth_credentials',
            'disable_socialite',
            'verify_email_before_login',
            'password_min_length',
            'session_timeout',
        ];

        $this->assertEquals($expectedKeys, $this->pipe->handles());
    }

    #[TestDox('sets socialite configuration when provided')]
    public function testSetsSocialiteConfiguration(): void
    {
        $tenantConfig = [
            'socialite_nonce_ttl' => 3600,
            'socialite_token_ttl' => 7200,
            'hmac_secret_key' => 'test-secret-key',
        ];

        $this->config->expects($this->exactly(3))
            ->method('set')
            ->willReturnCallback(function ($key, $value) use ($tenantConfig) {
                switch ($key) {
                    case 'auth.socialite.nonce_ttl':
                        $this->assertEquals($tenantConfig['socialite_nonce_ttl'], $value);
                        break;
                    case 'auth.socialite.token_ttl':
                        $this->assertEquals($tenantConfig['socialite_token_ttl'], $value);
                        break;
                    case 'auth.socialite.hmac_secret':
                        $this->assertEquals($tenantConfig['hmac_secret_key'], $value);
                        break;
                    default:
                        $this->fail("Unexpected config key: $key");
                }
            });

        $next = function ($payload) {
            return $payload;
        };

        $result = $this->pipe->handle($this->tenantModel, $this->config, $tenantConfig, $next);

        $this->assertEquals([
            'tenant' => $this->tenantModel,
            'config' => $this->config,
            'tenantConfig' => $tenantConfig,
        ], $result);
    }

    #[TestDox('sets socialite providers configuration')]
    public function testSetsSocialiteProvidersConfiguration(): void
    {
        $tenantConfig = [
            'socialite_providers' => ['github'], // Use a provider less likely to have env vars set
        ];

        // Expect at least the providers call, but allow additional calls for env-based credentials
        $this->config->expects($this->atLeast(1))
            ->method('set')
            ->willReturnCallback(function ($key, $value) {
                if ($key === 'auth.socialite.providers') {
                    $this->assertEquals(['github'], $value);
                }
                // Allow other service configuration calls
            });

        $next = function ($payload) {
            return $payload;
        };

        $this->pipe->handle($this->tenantModel, $this->config, $tenantConfig, $next);
    }

    #[TestDox('sets OAuth credentials with explicit redirect URLs')]
    public function testSetsOauthCredentialsWithExplicitRedirect(): void
    {
        $tenantConfig = [
            'oauth_credentials' => [
                'google' => [
                    'client_id' => 'google-client-id',
                    'client_secret' => 'google-client-secret',
                    'redirect' => 'https://custom.com/oauth/google/callback',
                ],
                'facebook' => [
                    'client_id' => 'facebook-client-id',
                    'client_secret' => 'facebook-client-secret',
                    'redirect' => 'https://custom.com/oauth/facebook/callback',
                ],
            ],
        ];

        $expectedCalls = [
            ['services.google.client_id', 'google-client-id'],
            ['services.google.client_secret', 'google-client-secret'],
            ['services.google.redirect', 'https://custom.com/oauth/google/callback'],
            ['services.facebook.client_id', 'facebook-client-id'],
            ['services.facebook.client_secret', 'facebook-client-secret'],
            ['services.facebook.redirect', 'https://custom.com/oauth/facebook/callback'],
        ];

        $this->config->expects($this->exactly(6))
            ->method('set')
            ->willReturnCallback(function ($key, $value) use (&$expectedCalls) {
                $found = false;
                foreach ($expectedCalls as $index => $expected) {
                    if ($expected[0] === $key && $expected[1] === $value) {
                        unset($expectedCalls[$index]);
                        $found = true;
                        break;
                    }
                }
                $this->assertTrue($found, "Unexpected call: set('$key', '$value')");
            });

        $next = function ($payload) {
            return $payload;
        };

        $this->pipe->handle($this->tenantModel, $this->config, $tenantConfig, $next);
    }

    #[TestDox('sets OAuth credentials with default redirect using tenant app URL')]
    public function testSetsOauthCredentialsWithDefaultRedirectUsingTenantAppUrl(): void
    {
        $tenantConfig = [
            'app_url' => 'https://tenant.com',
            'oauth_credentials' => [
                'google' => [
                    'client_id' => 'google-client-id',
                    'client_secret' => 'google-client-secret',
                    // No redirect specified - should use default
                ],
            ],
        ];

        $this->config->expects($this->exactly(3))
            ->method('set')
            ->willReturnCallback(function ($key, $value) {
                switch ($key) {
                    case 'services.google.client_id':
                        $this->assertEquals('google-client-id', $value);
                        break;
                    case 'services.google.client_secret':
                        $this->assertEquals('google-client-secret', $value);
                        break;
                    case 'services.google.redirect':
                        $this->assertEquals('https://tenant.com/auth/provider/google/callback', $value);
                        break;
                    default:
                        $this->fail("Unexpected config key: $key");
                }
            });

        $next = function ($payload) {
            return $payload;
        };

        $this->pipe->handle($this->tenantModel, $this->config, $tenantConfig, $next);
    }

    #[TestDox('sets OAuth credentials with default redirect using config app URL when tenant app URL not available')]
    public function testSetsOauthCredentialsWithDefaultRedirectUsingConfigAppUrl(): void
    {
        $tenantConfig = [
            // No app_url in tenant config
            'oauth_credentials' => [
                'github' => [
                    'client_id' => 'github-client-id',
                    // No redirect specified - should use config app.url
                ],
            ],
        ];

        $this->config->expects($this->exactly(2))
            ->method('set')
            ->willReturnCallback(function ($key, $value) {
                switch ($key) {
                    case 'services.github.client_id':
                        $this->assertEquals('github-client-id', $value);
                        break;
                    case 'services.github.redirect':
                        $this->assertEquals('https://config.app.url/auth/provider/github/callback', $value);
                        break;
                    default:
                        $this->fail("Unexpected config key: $key");
                }
            });

        $this->config->expects($this->once())
            ->method('get')
            ->with('app.url')
            ->willReturn('https://config.app.url');

        $next = function ($payload) {
            return $payload;
        };

        $this->pipe->handle($this->tenantModel, $this->config, $tenantConfig, $next);
    }

    #[TestDox('sets auth module specific settings')]
    public function testSetsAuthModuleSpecificSettings(): void
    {
        $tenantConfig = [
            'disable_socialite' => true,
            'verify_email_before_login' => false,
            'password_min_length' => 12,
            'session_timeout' => 86400,
        ];

        $this->config->expects($this->exactly(4))
            ->method('set')
            ->willReturnCallback(function ($key, $value) use ($tenantConfig) {
                switch ($key) {
                    case 'auth.disable_socialite':
                        $this->assertEquals($tenantConfig['disable_socialite'], $value);
                        break;
                    case 'auth.verify_email_before_login':
                        $this->assertEquals($tenantConfig['verify_email_before_login'], $value);
                        break;
                    case 'auth.password_min_length':
                        $this->assertEquals($tenantConfig['password_min_length'], $value);
                        break;
                    case 'auth.session_timeout':
                        $this->assertEquals($tenantConfig['session_timeout'], $value);
                        break;
                    default:
                        $this->fail("Unexpected config key: $key");
                }
            });

        $next = function ($payload) {
            return $payload;
        };

        $this->pipe->handle($this->tenantModel, $this->config, $tenantConfig, $next);
    }

    #[TestDox('skips configuration when keys not present')]
    public function testSkipsConfigurationWhenKeysNotPresent(): void
    {
        $tenantConfig = [
            'some_other_config' => 'value',
        ];

        // Should not call set() for any auth-related configs
        $this->config->expects($this->never())
            ->method('set');

        $this->config->expects($this->never())
            ->method('get');

        $next = function ($payload) {
            return $payload;
        };

        $result = $this->pipe->handle($this->tenantModel, $this->config, $tenantConfig, $next);

        $this->assertEquals([
            'tenant' => $this->tenantModel,
            'config' => $this->config,
            'tenantConfig' => $tenantConfig,
        ], $result);
    }

    #[TestDox('handles partial OAuth credentials')]
    public function testHandlesPartialOauthCredentials(): void
    {
        $tenantConfig = [
            'oauth_credentials' => [
                'google' => [
                    'client_id' => 'google-client-id',
                    // Missing client_secret and redirect
                ],
                'facebook' => [
                    'client_secret' => 'facebook-client-secret',
                    'redirect' => 'https://custom.com/facebook/callback',
                    // Missing client_id
                ],
            ],
        ];

        $expectedCalls = [
            ['services.google.client_id', 'google-client-id'],
            ['services.google.redirect', '/auth/provider/google/callback'], // Default redirect will be generated
            ['services.facebook.client_secret', 'facebook-client-secret'],
            ['services.facebook.redirect', 'https://custom.com/facebook/callback'],
        ];

        $this->config->expects($this->exactly(4))
            ->method('set')
            ->willReturnCallback(function ($key, $value) use (&$expectedCalls) {
                $found = false;
                foreach ($expectedCalls as $index => $expected) {
                    if ($expected[0] === $key && $expected[1] === $value) {
                        unset($expectedCalls[$index]);
                        $found = true;
                        break;
                    }
                }
                $this->assertTrue($found, "Unexpected call: set('$key', '$value')");
            });

        // Mock config->get for the default URL generation
        $this->config->expects($this->once())
            ->method('get')
            ->with('app.url')
            ->willReturn('');

        $next = function ($payload) {
            return $payload;
        };

        $this->pipe->handle($this->tenantModel, $this->config, $tenantConfig, $next);
    }

    #[TestDox('calls next with correct payload')]
    public function testCallsNextWithCorrectPayload(): void
    {
        $tenantConfig = ['test' => 'value'];

        $nextCalled = false;
        $next = function ($payload) use (&$nextCalled, $tenantConfig) {
            $nextCalled = true;

            $this->assertArrayHasKey('tenant', $payload);
            $this->assertArrayHasKey('config', $payload);
            $this->assertArrayHasKey('tenantConfig', $payload);

            $this->assertSame($this->tenantModel, $payload['tenant']);
            $this->assertSame($this->config, $payload['config']);
            $this->assertSame($tenantConfig, $payload['tenantConfig']);

            return 'next-result';
        };

        $result = $this->pipe->handle($this->tenantModel, $this->config, $tenantConfig, $next);

        $this->assertTrue($nextCalled);
        $this->assertEquals('next-result', $result);
    }
}
