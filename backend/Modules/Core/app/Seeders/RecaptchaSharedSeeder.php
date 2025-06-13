<?php

namespace Modules\Core\Seeders;

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
     * @param array $baseConfig The base configuration to build upon
     * @return array The shared configuration values to seed
     */
    public function getSharedConfig(string $template, array $baseConfig): array
    {
        $recaptchaConfig = [];

        // Use seed parameters or environment variables
        if (isset($baseConfig['_seed_recaptcha_site_key'])) {
            $recaptchaConfig['recaptcha_site_key'] = $baseConfig['_seed_recaptcha_site_key'];
            $recaptchaConfig['recaptcha_secret_key'] = $baseConfig['_seed_recaptcha_secret_key'] ?? '';
        } elseif (env('RECAPTCHA_GOOGLE_SITE_KEY')) {
            // Fallback to env for development
            $recaptchaConfig['recaptcha_site_key'] = env('RECAPTCHA_GOOGLE_SITE_KEY');
            $recaptchaConfig['recaptcha_secret_key'] = env('RECAPTCHA_GOOGLE_SECRET', '');
        }

        return $recaptchaConfig;
    }

    /**
     * Get visibility settings for the reCAPTCHA configuration.
     *
     * @return array Key-value pairs of config keys and their visibility levels
     */
    public function getVisibility(): array
    {
        return [
            'recaptcha_site_key' => 'public',
            'recaptcha_secret_key' => 'private',
        ];
    }

    /**
     * Get the priority for this shared seeder.
     *
     * @return int The priority level
     */
    public function getPriority(): int
    {
        return 15;
    }
}
