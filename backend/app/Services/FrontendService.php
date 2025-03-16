<?php

namespace App\Services;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class FrontendService
{
    protected readonly string $frontendUrl;

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
