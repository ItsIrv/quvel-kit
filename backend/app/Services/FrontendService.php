<?php

namespace App\Services;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Modules\Tenant\ValueObjects\TenantConfig;

class FrontendService
{
    private bool $isCapacitor;

    public function __construct(
        private readonly TenantConfig $config,
        private readonly Redirector $redirector,
        Request $request,
        private readonly ResponseFactory $responseFactory,
    ) {
        $this->isCapacitor = $request->hasHeader('X-Capacitor');
    }

    public function setIsCapacitor(bool $isCapacitor): void
    {
        $this->isCapacitor = $isCapacitor;
    }

    /**
     * Redirect to a frontend route, handling capacitor schemes if necessary.
     *
     * @param  array<string, string>  $query
     */
    public function redirect(string $path, array $query = []): RedirectResponse|Response
    {
        $finalUrl = $this->buildUrl($path, $query);

        if (! $this->isCapacitor || $this->config->capacitorScheme === '_deep') {
            return $this->redirector->away($finalUrl);
        }

        return $this->responseFactory->view('redirect', [
            'message' => null,
            'schemeUrl' => $finalUrl,
        ]);
    }

    /**
     * Get the full app URL, applying scheme override if capacitor is detected.
     *
     * @param  array<string, string>  $query
     */
    public function getPageUrl(string $path, array $query = []): string
    {
        return $this->buildUrl($path, $query);
    }

    /**
     * Build the final URL with scheme and query string support.
     *
     * @param  array<string, string>  $query
     */
    private function buildUrl(string $path, array $query = []): string
    {
        $url = rtrim($this->config->appUrl, '/').'/'.ltrim($path, '/');

        if (! empty($query)) {
            $url .= '?'.http_build_query($query);
        }

        // If it's a capacitor request and a custom scheme is defined
        if ($this->isCapacitor && $this->config->capacitorScheme && $this->config->capacitorScheme !== '_deep') {
            $url = preg_replace('/^https?/', $this->config->capacitorScheme, $url) ?? $url;
        }

        return $url;
    }
}
