<?php

namespace App\Services;

use Illuminate\Routing\Redirector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Modules\Tenant\ValueObjects\TenantConfig;

class FrontendService
{
    public function __construct(
        private readonly Redirector $redirector,
        private readonly TenantConfig $config,
    ) {}

    /**
     * Redirect to a frontend-relative path (e.g. "/dashboard")
     */
    public function redirect(string $to): RedirectResponse
    {
        $path = ltrim($to, '/');

        return $this->redirector->away("{$this->config->appUrl}/$path");
    }

    /**
     * Redirect to a frontend page with optional query parameters.
     *
     * @param array<string, string> $payload
     */
    public function redirectPage(string $page, array $payload = []): RedirectResponse
    {
        $uri = '/'.ltrim($page, '/');

        if (! empty($payload)) {
            $uri .= '?'.http_build_query($payload);
        }

        return $this->redirect($uri);
    }

    /**
     * Get the full URL of a frontend page with optional parameters.
     */
    public function getPageUrl(string $page, array $payload = []): string
    {
        $uri = '/'.ltrim($page, '/');

        if (! empty($payload)) {
            $uri .= '?'.http_build_query($payload);
        }

        return "{$this->config->appUrl}$uri";
    }

    /**
     * Redirect back to the device using capacitor scheme, or fallback to given response.
     */
    public function redirectToDeviceOrFallback(callable $fallback): RedirectResponse|Response
    {
        if ($this->config->capacitorScheme === '_DEEP') {
            return $this->redirect('/');
        }

        if (! empty($this->config->capacitorScheme)) {
            $url = "{$this->config->capacitorScheme}://{$this->config->appUrl}";
            return $this->redirector->away($url);
        }

        return $fallback();
    }
}
