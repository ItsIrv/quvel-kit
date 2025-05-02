<?php

namespace Modules\Core\Services;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;

class FrontendService
{
    private string $url;
    private ?string $capacitorScheme;
    private bool $isCapacitor;

    public function __construct(
        private readonly Redirector $redirector,
        private readonly ResponseFactory $responseFactory,
    ) {
        $this->url             = '';
        $this->capacitorScheme = null;
        $this->isCapacitor     = false;
    }

    /**
     * Set the URL.
     */
    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get the URL.
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Set the capacitor scheme.
     */
    public function setCapacitorScheme(?string $capacitorScheme): static
    {
        $this->capacitorScheme = $capacitorScheme;

        return $this;
    }

    /**
     * Get the capacitor scheme.
     */
    public function getCapacitorScheme(): ?string
    {
        return $this->capacitorScheme;
    }

    /**
     * Set the capacitor flag.
     */
    public function setIsCapacitor(bool $isCapacitor): static
    {
        $this->isCapacitor = $isCapacitor;

        return $this;
    }

    /**
     * Get the capacitor flag.
     */
    public function getIsCapacitor(): bool
    {
        return $this->isCapacitor;
    }

    /**
     * Redirect to a frontend route, handling capacitor schemes if necessary.
     *
     * @param  array<string, string>  $query
     */
    public function redirect(string $path = '', array $query = []): RedirectResponse|Response
    {
        $finalUrl = $this->buildUrl($path, $query);

        if (!$this->isCapacitor || $this->capacitorScheme === '_deep') {
            return $this->redirector->away($finalUrl);
        }

        return $this->responseFactory->view('redirect', [
            'message'   => null,
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
        $url = $this->url . '/' . $path;

        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        // If it's a capacitor request and a custom scheme is defined
        if ($this->isCapacitor && $this->capacitorScheme && $this->capacitorScheme !== '_deep') {
            $url = preg_replace('/^https?/', $this->capacitorScheme, $url) ?? $url;
        }

        return $url;
    }
}
