<?php

namespace Modules\Tenant\Pipes;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\Tenant\Contracts\ConfigurationPipeInterface;
use Modules\Tenant\Models\Tenant;

class ServicesConfigPipe implements ConfigurationPipeInterface
{
    public function handle(Tenant $tenant, ConfigRepository $config, array $tenantConfig, callable $next): mixed
    {
        // Configure Stripe payment gateway
        if (isset($tenantConfig['stripe_key'])) {
            $config->set('services.stripe.key', $tenantConfig['stripe_key']);
            if (isset($tenantConfig['stripe_secret'])) {
                $config->set('services.stripe.secret', $tenantConfig['stripe_secret']);
            }

            if (isset($tenantConfig['stripe_webhook_secret'])) {
                $config->set('services.stripe.webhook_secret', $tenantConfig['stripe_webhook_secret']);
            }
        }

        // Configure PayPal
        if (isset($tenantConfig['paypal_client_id'])) {
            $config->set('services.paypal.client_id', $tenantConfig['paypal_client_id']);
            if (isset($tenantConfig['paypal_secret'])) {
                $config->set('services.paypal.secret', $tenantConfig['paypal_secret']);
            }
            $config->set('services.paypal.mode', $tenantConfig['paypal_mode'] ?? 'sandbox');
        }

        // Configure Twilio for SMS
        if (isset($tenantConfig['twilio_sid'])) {
            $config->set('services.twilio.sid', $tenantConfig['twilio_sid']);
            if (isset($tenantConfig['twilio_token'])) {
                $config->set('services.twilio.token', $tenantConfig['twilio_token']);
            }
            if (isset($tenantConfig['twilio_from'])) {
                $config->set('services.twilio.from', $tenantConfig['twilio_from']);
            }
        }

        // Configure SendGrid
        if (isset($tenantConfig['sendgrid_api_key'])) {
            $config->set('services.sendgrid.api_key', $tenantConfig['sendgrid_api_key']);
        }

        // Configure Mailgun
        if (isset($tenantConfig['mailgun_domain'])) {
            $config->set('services.mailgun.domain', $tenantConfig['mailgun_domain']);
            if (isset($tenantConfig['mailgun_secret'])) {
                $config->set('services.mailgun.secret', $tenantConfig['mailgun_secret']);
            }
            $config->set('services.mailgun.endpoint', $tenantConfig['mailgun_endpoint'] ?? 'api.mailgun.net');
        }

        // Configure Postmark
        if (isset($tenantConfig['postmark_token'])) {
            $config->set('services.postmark.token', $tenantConfig['postmark_token']);
        }

        // Configure AWS SES
        if (isset($tenantConfig['ses_key'])) {
            $config->set('services.ses.key', $tenantConfig['ses_key']);
            if (isset($tenantConfig['ses_secret'])) {
                $config->set('services.ses.secret', $tenantConfig['ses_secret']);
            }
            $config->set('services.ses.region', $tenantConfig['ses_region'] ?? 'us-east-1');
        }

        // Configure Algolia search
        if (isset($tenantConfig['algolia_app_id'])) {
            $config->set('services.algolia.app_id', $tenantConfig['algolia_app_id']);
            if (isset($tenantConfig['algolia_secret'])) {
                $config->set('services.algolia.secret', $tenantConfig['algolia_secret']);
            }
        }

        // Configure Google Analytics
        if (isset($tenantConfig['google_analytics_id'])) {
            $config->set('services.google_analytics.tracking_id', $tenantConfig['google_analytics_id']);
        }

        // Configure Google Maps
        if (isset($tenantConfig['google_maps_key'])) {
            $config->set('services.google_maps.key', $tenantConfig['google_maps_key']);
        }

        // Configure Bugsnag error tracking
        if (isset($tenantConfig['bugsnag_api_key'])) {
            $config->set('services.bugsnag.api_key', $tenantConfig['bugsnag_api_key']);
        }

        // Configure Slack incoming webhooks
        if (isset($tenantConfig['slack_webhook_url'])) {
            $config->set('services.slack.webhook_url', $tenantConfig['slack_webhook_url']);
        }

        // Configure custom API services
        if (isset($tenantConfig['custom_api_endpoints'])) {
            foreach ($tenantConfig['custom_api_endpoints'] as $service => $endpoint) {
                $config->set('services.custom.' . $service . '.endpoint', $endpoint);

                // Check for API keys
                $apiKeyConfig = 'custom_api_keys.' . $service;
                if (isset($tenantConfig[$apiKeyConfig])) {
                    $config->set('services.custom.' . $service . '.key', $tenantConfig[$apiKeyConfig]);
                }
            }
        }

        // Pass to next pipe
        return $next([
            'tenant'       => $tenant,
            'config'       => $config,
            'tenantConfig' => $tenantConfig,
        ]);
    }

    public function handles(): array
    {
        return [
            'stripe_key',
            'stripe_secret',
            'stripe_webhook_secret',
            'paypal_client_id',
            'paypal_secret',
            'paypal_mode',
            'twilio_sid',
            'twilio_token',
            'twilio_from',
            'sendgrid_api_key',
            'mailgun_domain',
            'mailgun_secret',
            'mailgun_endpoint',
            'postmark_token',
            'ses_key',
            'ses_secret',
            'ses_region',
            'algolia_app_id',
            'algolia_secret',
            'google_analytics_id',
            'google_maps_key',
            'bugsnag_api_key',
            'slack_webhook_url',
            'custom_api_endpoints',
            'custom_api_keys',
        ];
    }

    public function priority(): int
    {
        return 35; // Run after logging pipe
    }
}
