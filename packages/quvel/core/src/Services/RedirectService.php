<?php

declare(strict_types=1);

namespace Quvel\Core\Services;

use Illuminate\Http\Request;
use Quvel\Core\Enums\HttpHeader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

use function app;
use function http_build_query;
use function ltrim;
use function preg_replace;
use function rtrim;

/**
 * Service for redirecting users back to the frontend application.
 * Handles platform-specific URL schemes and query parameter building.
 */
class RedirectService
{
    private string $baseUrl = '';
    private ?string $customScheme = null;

    /**
     * Set the base frontend URL.
     */
    public function setBaseUrl(string $url): self
    {
        $this->baseUrl = $url;
        return $this;
    }

    /**
     * Set custom URL scheme for deep linking.
     */
    public function setCustomScheme(?string $scheme): self
    {
        $this->customScheme = $scheme;
        return $this;
    }

    /**
     * Redirect to a frontend route with optional query parameters.
     */
    public function redirect(string $path = '', array $queryParams = []): RedirectResponse|Response
    {
        $url = $this->buildUrl($path, $queryParams);

        // For platforms that need custom schemes, use a redirect view
        if ($this->shouldUseCustomScheme()) {
            return app('response')->view('redirect', [
                'url' => $url,
                'platform' => $this->getPlatform(),
                'scheme' => $this->customScheme,
            ]);
        }

        return app('redirect')->away($url);
    }

    /**
     * Redirect with a message parameter (syncs with frontend composables).
     */
    public function redirectWithMessage(string $path, string $message, array $extraParams = []): RedirectResponse|Response
    {
        $queryParams = array_merge(['message' => $message], $extraParams);
        return $this->redirect($path, $queryParams);
    }

    /**
     * Get the full frontend URL without redirecting.
     */
    public function getUrl(string $path = '', array $queryParams = []): string
    {
        return $this->buildUrl($path, $queryParams);
    }

    /**
     * Get URL with message parameter.
     */
    public function getUrlWithMessage(string $path, string $message, array $extraParams = []): string
    {
        $queryParams = array_merge(['message' => $message], $extraParams);
        return $this->getUrl($path, $queryParams);
    }

    /**
     * Check if current request is from a specific platform.
     */
    public function isPlatform(string $platform): bool
    {
        return $this->getPlatform() === $platform;
    }

    /**
     * Get the detected platform.
     */
    public function getPlatform(): string
    {
        /**
         * @var Request $request
         */
        $request = app('request');
        $platformHeader = $request->header(HttpHeader::PLATFORM->getValue());

        return match ($platformHeader) {
            'capacitor', 'cordova' => 'mobile',
            'electron', 'tauri' => 'desktop',
            default => 'web',
        };
    }

    /**
     * Build the complete URL with query parameters.
     */
    private function buildUrl(string $path, array $queryParams): string
    {
        $base = rtrim($this->baseUrl, '/');
        $path = ltrim($path, '/');

        $url = $path ? "$base/$path" : $base;

        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }

        if ($this->shouldUseCustomScheme()) {
            $url = preg_replace('#^https?://#', $this->customScheme . '://', $url) ?? $url;
        }

        return $url;
    }

    /**
     * Determine if custom scheme should be used.
     */
    private function shouldUseCustomScheme(): bool
    {
        return $this->customScheme !== null
            && $this->customScheme !== '_deep'
            && $this->getPlatform() !== 'web';
    }
}