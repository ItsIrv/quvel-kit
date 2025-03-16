<?php

namespace App\Services;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

// TODO: should be scoped to TenantContext to correctly handle tenant-specific frontend URLs.
class FrontendService
{
    protected string $frontendUrl;

    public function __construct(string $frontendUrl)
    {
        $this->frontendUrl = rtrim($frontendUrl, '/');
    }

    /**
     * Redirect to a specific frontend route.
     */
    public function redirect(string $to): RedirectResponse
    {
        return Redirect::away("$this->frontendUrl$to");
    }

    /**
     * Redirect to a specific frontend page with optional parameters.
     *
     * @param  array<string, string>  $payload
     */
    public function redirectPage(string $page, array $payload = []): RedirectResponse
    {
        $uri = "/$page";
        if (! empty($payload)) {
            $uri .= '?'.http_build_query($payload);
        }

        return $this->redirect($uri);
    }

    /**
     * Redirect to the login page with optional parameters.
     *
     * @param  array<string, string>  $payload
     */
    public function redirectLogin(array $payload = []): RedirectResponse
    {
        return $this->redirectPage(
            'login',
            $payload,
        );
    }

    /**
     * Redirect to login with status messages.
     */
    public function redirectLoginStatus(string $type, string $message): RedirectResponse
    {
        return $this->redirectLogin([
            'type' => $type,
            'message' => $message,
        ]);
    }

    /**
     * Get the full URL of a frontend page with optional parameters.
     *
     * @param  array<string, string>  $payload
     */
    public function getPageUrl(string $page, array $payload = []): string
    {
        $uri = "/$page";

        if (! empty($payload)) {
            $uri .= '?'.http_build_query($payload);
        }

        return "$this->frontendUrl$uri";
    }
}
