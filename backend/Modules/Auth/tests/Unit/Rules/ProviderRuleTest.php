<?php

namespace Modules\Auth\Tests\Unit\Rules;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Validator;
use Mockery;
use Modules\Auth\Enums\OAuthStatusEnum;
use Modules\Auth\Rules\ProviderRule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(ProviderRule::class)]
#[Group('auth-module')]
#[Group('auth-rules')]
class ProviderRuleTest extends TestCase
{
    private ConfigRepository $configMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->configMock = Mockery::mock(ConfigRepository::class);
        $this->configMock->shouldReceive('get')
            ->with('auth.oauth.providers', [])
            ->andReturn(['google', 'facebook', 'apple']);
    }

    #[DataProvider('providerData')]
    public function testProviderRule(string $provider, bool $shouldPass): void
    {
        $validator = Validator::make(
            ['provider' => $provider],
            ['provider' => ProviderRule::RULES($this->configMock)],
        );

        $passes = !$validator->fails();
        $this->assertEquals($shouldPass, $passes, "Failed asserting that '{$provider}' validation is correct.");
    }

    public static function providerData(): array
    {
        return [
            'valid provider (google)'   => ['google', true],
            'valid provider (facebook)' => ['facebook', false],
            'valid provider (apple)'    => ['apple', false],
            'invalid provider (xyz)'    => ['xyz', false],
        ];
    }

    public function testInvalidProviderFailsWithCorrectMessage(): void
    {
        // Mock Translator
        $translatorMock = Mockery::mock(\Illuminate\Contracts\Translation\Translator::class);
        $translatorMock->shouldReceive('get')
            ->times(3)
            ->andReturn('Invalid provider.');

        // Bind the mock to the container
        $this->app->instance('translator', $translatorMock);

        // Run validation
        $validator = Validator::make(
            ['provider' => 'invalid_provider'],
            ['provider' => ProviderRule::RULES($this->configMock)],
        );

        $fails         = $validator->fails();
        $errorMessages = $validator->errors()->get('provider');

        $this->assertTrue($fails, 'Validation should fail for an invalid provider.');
        $this->assertNotEmpty($errorMessages, 'Validation should return an error message.');
        $this->assertStringContainsString(
            'Invalid provider.',
            $errorMessages[0],
            'Error message should match translated OAuthStatusEnum::INVALID_PROVIDER.',
        );
    }
}
