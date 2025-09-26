<?php

namespace Modules\Tenant\Seeders\Shared;

use Modules\Tenant\Contracts\TenantSharedSeederInterface;

/**
 * Shared seeder for reCAPTCHA configuration.
 *
 * Provides reCAPTCHA configuration that applies to all tenant templates.
 */
class RecaptchaSharedSeeder implements TenantSharedSeederInterface
{
    /**
     * Generate shared reCAPTCHA configuration for all templates.
     *
     * @param string $template The tenant template for context
     * @param array<string, mixed> $baseConfig The base configuration to build upon
     * @return array<string, mixed> The shared configuration values to seed
     */
    public function getSharedConfig(string $template, array $baseConfig): array
    {
        $recaptchaConfig = [];

        // Use direct parameters or environment variables for fallback
        if (isset($baseConfig['recaptcha_site_key'])) {
            $recaptchaConfig['recaptcha_site_key']   = $baseConfig['recaptcha_site_key'];
            $recaptchaConfig['recaptcha_secret_key'] = $baseConfig['recaptcha_secret_key'] ?? '';
        } elseif (config('core.recaptcha.recaptcha_site_key') !== null) {
            // Fallback to core config
            $recaptchaConfig['recaptcha_site_key']   = config('core.recaptcha.recaptcha_site_key');
            $recaptchaConfig['recaptcha_secret_key'] = config('core.recaptcha.recaptcha_secret_key', '');
        }

        return $recaptchaConfig;
    }

    /**
     * Get visibility settings for the reCAPTCHA configuration.
     *
     * @return array<string, mixed> Key-value pairs of config keys and their visibility levels
     */
    public function getVisibility(): array
    {
        return [
            'recaptcha_site_key'   => 'public',
            'recaptcha_secret_key' => 'private',
        ];
    }
}
