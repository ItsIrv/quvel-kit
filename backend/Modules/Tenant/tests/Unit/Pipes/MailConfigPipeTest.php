<?php

namespace Modules\Tenant\Tests\Unit\Pipes;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Pipes\MailConfigPipe;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the MailConfigPipe class.
 */
#[CoversClass(MailConfigPipe::class)]
#[Group('tenant-module')]
#[Group('tenant-pipes')]
class MailConfigPipeTest extends TestCase
{
    private MailConfigPipe $pipe;
    private ConfigRepository|MockObject $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = $this->createMock(ConfigRepository::class);
        $this->pipe   = new MailConfigPipe();
    }

    public function testHandleAppliesAllMailConfig(): void
    {
        $tenant       = $this->createMock(Tenant::class);
        $tenantConfig = [
            'mail_mailer'       => 'mailgun',
            'mail_host'         => 'smtp.mailgun.org',
            'mail_port'         => 587,
            'mail_username'     => 'tenant@mailgun.org',
            'mail_password'     => 'secret-password',
            'mail_encryption'   => 'tls',
            'mail_from_address' => 'noreply@tenant.com',
            'mail_from_name'    => 'Tenant Name',
        ];

        $expectedSets = [
            ['mail.default', 'mailgun'],
            ['mail.mailers.smtp.host', 'smtp.mailgun.org'],
            ['mail.mailers.smtp.port', 587],
            ['mail.mailers.smtp.username', 'tenant@mailgun.org'],
            ['mail.mailers.smtp.password', 'secret-password'],
            ['mail.mailers.smtp.encryption', 'tls'],
            ['mail.from.address', 'noreply@tenant.com'],
            ['mail.from.name', 'Tenant Name'],
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
        $this->assertSame($this->config, $result['config']);
        $this->assertSame($tenantConfig, $result['tenantConfig']);
    }

    #[DataProvider('partialConfigProvider')]
    public function testHandleWithPartialConfig(array $tenantConfig, array $expectedSets): void
    {
        $tenant = $this->createMock(Tenant::class);

        if (!empty($expectedSets)) {
            $this->config->expects($this->exactly(count($expectedSets)))
                ->method('set')
                ->willReturnCallback(function ($key, $value) use (&$expectedSets) {
                    $expected = array_shift($expectedSets);
                    $this->assertEquals($expected[0], $key);
                    $this->assertEquals($expected[1], $value);
                    return null;
                });
        } else {
            $this->config->expects($this->never())->method('set');
        }

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
    }

    public static function partialConfigProvider(): array
    {
        return [
            'only mailer'                       => [
                ['mail_mailer' => 'ses'],
                [['mail.default', 'ses']],
            ],
            'only from address'                 => [
                ['mail_from_address' => 'hello@example.com'],
                [['mail.from.address', 'hello@example.com']],
            ],
            'smtp configuration without mailer' => [
                [
                    'mail_host'       => 'smtp.gmail.com',
                    'mail_port'       => 465,
                    'mail_encryption' => 'ssl',
                ],
                [
                    ['mail.mailers.smtp.host', 'smtp.gmail.com'],
                    ['mail.mailers.smtp.port', 465],
                    ['mail.mailers.smtp.encryption', 'ssl'],
                ],
            ],
            'from configuration only'           => [
                [
                    'mail_from_address' => 'support@tenant.com',
                    'mail_from_name'    => 'Support Team',
                ],
                [
                    ['mail.from.address', 'support@tenant.com'],
                    ['mail.from.name', 'Support Team'],
                ],
            ],
            'empty config'                      => [
                [],
                [],
            ],
        ];
    }

    public function testHandleWithNumericValues(): void
    {
        $tenant       = $this->createMock(Tenant::class);
        $tenantConfig = [
            'mail_port'      => '587', // String that should be treated as integer
            'mail_from_name' => 'Tenant 123', // Name with numbers
        ];

        $this->config->expects($this->exactly(2))
            ->method('set')
            ->willReturnCallback(function ($key, $value) {
                if ($key === 'mail.mailers.smtp.port') {
                    $this->assertEquals('587', $value);
                } elseif ($key === 'mail.from.name') {
                    $this->assertEquals('Tenant 123', $value);
                }
                return null;
            });

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
    }

    public function testHandlePassesDataToNextPipe(): void
    {
        $tenant       = $this->createMock(Tenant::class);
        $tenantConfig = ['mail_mailer' => 'smtp'];
        $nextCalled   = false;

        $this->config->expects($this->once())
            ->method('set')
            ->with('mail.default', 'smtp');

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) use (&$nextCalled) {
            $nextCalled = true;
            $this->assertArrayHasKey('tenant', $data);
            $this->assertArrayHasKey('config', $data);
            $this->assertArrayHasKey('tenantConfig', $data);
            return $data;
        });

        $this->assertTrue($nextCalled);
        $this->assertSame($tenant, $result['tenant']);
    }

    public function testHandlesReturnsCorrectKeys(): void
    {
        $handles = $this->pipe->handles();

        $expectedKeys = [
            'mail_mailer',
            'mail_host',
            'mail_port',
            'mail_username',
            'mail_password',
            'mail_encryption',
            'mail_from_address',
            'mail_from_name',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertContains($key, $handles);
        }
        $this->assertCount(count($expectedKeys), $handles);
    }

    public function testPriorityReturnsCorrectValue(): void
    {
        $priority = $this->pipe->priority();

        $this->assertEquals(70, $priority);
    }

    public function testHandleWithSpecialCharactersInPassword(): void
    {
        $tenant       = $this->createMock(Tenant::class);
        $tenantConfig = [
            'mail_password'  => 'p@$$w0rd!#$%^&*()',
            'mail_from_name' => 'Company & Co. <info@company.com>',
        ];

        $this->config->expects($this->exactly(2))
            ->method('set')
            ->willReturnCallback(function ($key, $value) {
                if ($key === 'mail.mailers.smtp.password') {
                    $this->assertEquals('p@$$w0rd!#$%^&*()', $value);
                } elseif ($key === 'mail.from.name') {
                    $this->assertEquals('Company & Co. <info@company.com>', $value);
                }
                return null;
            });

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
    }
}
