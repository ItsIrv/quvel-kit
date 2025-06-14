<?php

namespace Modules\Tenant\Tests\Unit\Pipes;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Pipes\ServicesConfigPipe;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the ServicesConfigPipe class.
 */
#[CoversClass(ServicesConfigPipe::class)]
#[Group('tenant-module')]
#[Group('tenant-pipes')]
class ServicesConfigPipeTest extends TestCase
{
    private ServicesConfigPipe $pipe;
    private ConfigRepository|MockObject $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = $this->createMock(ConfigRepository::class);
        $this->pipe = new ServicesConfigPipe();
    }

    public function testResolveReturnsCorrectValuesAndVisibility(): void
    {
        $tenant = $this->createMock(Tenant::class);
        $tenantConfig = [
            'recaptcha_site_key' => 'test-site-key',
        ];

        $result = $this->pipe->resolve($tenant, $tenantConfig);

        $expectedValues = [
            'recaptchaGoogleSiteKey' => 'test-site-key',
        ];

        $expectedVisibility = [
            'recaptchaGoogleSiteKey' => 'public',
        ];

        $this->assertEquals($expectedValues, $result['values']);
        $this->assertEquals($expectedVisibility, $result['visibility']);
    }

    public function testResolveWithEmptyConfig(): void
    {
        $tenant = $this->createMock(Tenant::class);
        $result = $this->pipe->resolve($tenant, []);
        $this->assertEquals(['values' => [], 'visibility' => []], $result);
    }



    public function testHandleConfiguresStripe(): void
    {
        $tenant = $this->createMock(Tenant::class);
        $tenantConfig = [
            'stripe_key' => 'pk_test_123',
            'stripe_secret' => 'sk_test_456',
            'stripe_webhook_secret' => 'whsec_789',
        ];

        $this->config->expects($this->any())
            ->method('get')
            ->willReturn([]);

        $expectedSets = [
            ['services.stripe.key', 'pk_test_123'],
            ['services.stripe.secret', 'sk_test_456'],
            ['services.stripe.webhook_secret', 'whsec_789'],
        ];

        $this->config->expects($this->exactly(count($expectedSets)))
            ->method('set')
            ->willReturnCallback(function ($key, $value) use (&$expectedSets) {
                $expected = array_shift($expectedSets);
                $this->assertEquals($expected[0], $key);
                $this->assertEquals($expected[1], $value);
                return null;
            });

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
    }

    public function testHandleConfiguresPayPal(): void
    {
        $tenant = $this->createMock(Tenant::class);
        $tenantConfig = [
            'paypal_client_id' => 'AYSq3RDGsmBLJE-otTkBtM',
            'paypal_secret' => 'EGnHDxD_qRPdaLdZz8iCr8N7',
            'paypal_mode' => 'live',
        ];

        $this->config->expects($this->any())
            ->method('get')
            ->willReturn([]);

        $expectedSets = [
            ['services.paypal.client_id', 'AYSq3RDGsmBLJE-otTkBtM'],
            ['services.paypal.secret', 'EGnHDxD_qRPdaLdZz8iCr8N7'],
            ['services.paypal.mode', 'live'],
        ];

        $this->config->expects($this->exactly(count($expectedSets)))
            ->method('set')
            ->willReturnCallback(function ($key, $value) use (&$expectedSets) {
                $expected = array_shift($expectedSets);
                $this->assertEquals($expected[0], $key);
                $this->assertEquals($expected[1], $value);
                return null;
            });

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
    }

    public function testHandleConfiguresTwilio(): void
    {
        $tenant = $this->createMock(Tenant::class);
        $tenantConfig = [
            'twilio_sid' => 'ACXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
            'twilio_token' => 'your_auth_token',
            'twilio_from' => '+15017122661',
        ];

        $this->config->expects($this->any())
            ->method('get')
            ->willReturn([]);

        $expectedSets = [
            ['services.twilio.sid', 'ACXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'],
            ['services.twilio.token', 'your_auth_token'],
            ['services.twilio.from', '+15017122661'],
        ];

        $this->config->expects($this->exactly(count($expectedSets)))
            ->method('set')
            ->willReturnCallback(function ($key, $value) use (&$expectedSets) {
                $expected = array_shift($expectedSets);
                $this->assertEquals($expected[0], $key);
                $this->assertEquals($expected[1], $value);
                return null;
            });

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
    }

    public function testHandleConfiguresMailServices(): void
    {
        $tenant = $this->createMock(Tenant::class);
        $tenantConfig = [
            'sendgrid_api_key' => 'SG.xxxxx',
            'mailgun_domain' => 'mg.example.com',
            'mailgun_secret' => 'key-xxxxx',
            'mailgun_endpoint' => 'api.eu.mailgun.net',
            'postmark_token' => 'xxxxx-xxxxx-xxxxx-xxxxx-xxxxx',
        ];

        $this->config->expects($this->any())
            ->method('get')
            ->willReturn([]);

        $expectedSets = [
            ['services.sendgrid.api_key', 'SG.xxxxx'],
            ['services.mailgun.domain', 'mg.example.com'],
            ['services.mailgun.secret', 'key-xxxxx'],
            ['services.mailgun.endpoint', 'api.eu.mailgun.net'],
            ['services.postmark.token', 'xxxxx-xxxxx-xxxxx-xxxxx-xxxxx'],
        ];

        $callIndex = 0;
        $this->config->expects($this->exactly(count($expectedSets)))
            ->method('set')
            ->willReturnCallback(function ($key, $value) use (&$expectedSets, &$callIndex) {
                $expected = $expectedSets[$callIndex];
                $this->assertEquals($expected[0], $key);
                $this->assertEquals($expected[1], $value);
                $callIndex++;
                return null;
            });

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
    }

    public function testHandleConfiguresAWSSES(): void
    {
        $tenant = $this->createMock(Tenant::class);
        $tenantConfig = [
            'ses_key' => 'AKIAIOSFODNN7EXAMPLE',
            'ses_secret' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            'ses_region' => 'eu-west-1',
        ];

        $this->config->expects($this->any())
            ->method('get')
            ->willReturn([]);

        $expectedSets = [
            ['services.ses.key', 'AKIAIOSFODNN7EXAMPLE'],
            ['services.ses.secret', 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY'],
            ['services.ses.region', 'eu-west-1'],
        ];

        $this->config->expects($this->exactly(count($expectedSets)))
            ->method('set')
            ->willReturnCallback(function ($key, $value) use (&$expectedSets) {
                $expected = array_shift($expectedSets);
                $this->assertEquals($expected[0], $key);
                $this->assertEquals($expected[1], $value);
                return null;
            });

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
    }

    public function testHandleConfiguresSearchAndAnalytics(): void
    {
        $tenant = $this->createMock(Tenant::class);
        $tenantConfig = [
            'algolia_app_id' => 'YourApplicationID',
            'algolia_secret' => 'YourAdminAPIKey',
            'google_analytics_id' => 'UA-123456-1',
            'google_maps_key' => 'AIzaSyDxxxxx',
        ];

        $this->config->expects($this->any())
            ->method('get')
            ->willReturn([]);

        $expectedSets = [
            ['services.algolia.app_id', 'YourApplicationID'],
            ['services.algolia.secret', 'YourAdminAPIKey'],
            ['services.google_analytics.tracking_id', 'UA-123456-1'],
            ['services.google_maps.key', 'AIzaSyDxxxxx'],
        ];

        $callIndex = 0;
        $this->config->expects($this->exactly(count($expectedSets)))
            ->method('set')
            ->willReturnCallback(function ($key, $value) use (&$expectedSets, &$callIndex) {
                $expected = $expectedSets[$callIndex];
                $this->assertEquals($expected[0], $key);
                $this->assertEquals($expected[1], $value);
                $callIndex++;
                return null;
            });

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
    }

    public function testHandleConfiguresErrorTracking(): void
    {
        $tenant = $this->createMock(Tenant::class);
        $tenantConfig = [
            'bugsnag_api_key' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
            'slack_webhook_url' => 'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX',
        ];

        $this->config->expects($this->any())
            ->method('get')
            ->willReturn([]);

        $expectedSets = [
            ['services.bugsnag.api_key', 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxx'],
            ['services.slack.webhook_url', 'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX'],
        ];

        $this->config->expects($this->exactly(count($expectedSets)))
            ->method('set')
            ->willReturnCallback(function ($key, $value) use (&$expectedSets) {
                $expected = array_shift($expectedSets);
                $this->assertEquals($expected[0], $key);
                $this->assertEquals($expected[1], $value);
                return null;
            });

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
    }

    public function testHandleConfiguresCustomAPIEndpoints(): void
    {
        $tenant = $this->createMock(Tenant::class);
        $tenantConfig = [
            'custom_api_endpoints' => [
                'weather' => 'https://api.weather.com/v1',
                'geocoding' => 'https://api.geocoding.com/v2',
            ],
            'custom_api_keys.weather' => 'weather-api-key',
            'custom_api_keys.geocoding' => 'geocoding-api-key',
        ];

        $this->config->expects($this->any())
            ->method('get')
            ->willReturn([]);

        $expectedSets = [
            ['services.custom.weather.endpoint', 'https://api.weather.com/v1'],
            ['services.custom.weather.key', 'weather-api-key'],
            ['services.custom.geocoding.endpoint', 'https://api.geocoding.com/v2'],
            ['services.custom.geocoding.key', 'geocoding-api-key'],
        ];

        $setCalls = [];
        $this->config->expects($this->exactly(count($expectedSets)))
            ->method('set')
            ->willReturnCallback(function ($key, $value) use (&$setCalls) {
                $setCalls[] = [$key, $value];
                return null;
            });

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        // Sort both arrays to ensure order doesn't matter
        sort($expectedSets);
        sort($setCalls);

        $this->assertEquals($expectedSets, $setCalls);
        $this->assertSame($tenant, $result['tenant']);
    }

    #[DataProvider('partialConfigProvider')]
    public function testHandleWithPartialConfig(array $tenantConfig, int $expectedSetCalls): void
    {
        $tenant = $this->createMock(Tenant::class);

        $this->config->expects($this->any())
            ->method('get')
            ->willReturn([]);

        $this->config->expects($this->exactly($expectedSetCalls))
            ->method('set');

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
    }

    public static function partialConfigProvider(): array
    {
        return [
            'stripe without webhook' => [
                [
                    'stripe_key' => 'pk_test_123',
                    'stripe_secret' => 'sk_test_456',
                ],
                2,
            ],
            'stripe without secret' => [
                [
                    'stripe_key' => 'pk_test_123',
                ],
                1,
            ],
            'paypal with default mode' => [
                [
                    'paypal_client_id' => 'client_id',
                    'paypal_secret' => 'secret',
                ],
                3, // includes default sandbox mode
            ],
            'ses with default region' => [
                [
                    'ses_key' => 'key',
                    'ses_secret' => 'secret',
                ],
                3, // includes default us-east-1 region
            ],
            'mailgun without custom endpoint' => [
                [
                    'mailgun_domain' => 'mg.example.com',
                    'mailgun_secret' => 'key-xxxxx',
                ],
                3, // includes default api.mailgun.net endpoint
            ],
            'empty config' => [
                [],
                0,
            ],
        ];
    }



}
