<?php

declare(strict_types=1);

namespace Quvel\Core\Http\Middleware;

use Quvel\Core\Enums\HttpHeader;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;


/**
 * Enhanced locale middleware with configurable locale detection.
 */
class LocaleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $locale = $this->detectLocale($request);

        if ($locale && $this->isAllowedLocale($locale)) {
            App::setLocale($this->normalizeLocale($locale));
        }

        return $next($request);
    }

    /**
     * Detect locale from request.
     */
    private function detectLocale(Request $request): ?string
    {
        $headerValue = $request->header(HttpHeader::ACCEPT_LANGUAGE->getValue());

        if (!$headerValue) {
            return config('core.locale.fallback_locale', 'en');
        }

        if (str_contains($headerValue, ',')) {
            return $this->parseAcceptLanguageHeader($headerValue);
        }

        return $headerValue;
    }

    /**
     * Parse Accept-Language header with quality values.
     */
    private function parseAcceptLanguageHeader(string $header): ?string
    {
        $locales = [];

        foreach (explode(',', $header) as $locale) {
            $parts = explode(';q=', trim($locale));
            $lang = trim($parts[0]);
            $quality = isset($parts[1]) ? (float) $parts[1] : 1.0;

            if ($this->isAllowedLocale($lang)) {
                $locales[$lang] = $quality;
            }
        }

        if (empty($locales)) {
            return config('core.locale.fallback_locale', 'en');
        }

        arsort($locales);

        return array_key_first($locales);
    }

    /**
     * Check if locale is allowed.
     */
    private function isAllowedLocale(string $locale): bool
    {
        $allowedLocales = config('core.locale.allowed_locales', ['en']);

        if (in_array($locale, $allowedLocales, true)) {
            return true;
        }

        if (config('core.locale.normalize_locales', true)) {
            $normalized = $this->normalizeLocale($locale);
            $normalizedAllowed = array_map([$this, 'normalizeLocale'], $allowedLocales);

            return in_array($normalized, $normalizedAllowed, true);
        }

        return false;
    }

    /**
     * Normalize locale (e.g., "en-US" -> "en").
     */
    private function normalizeLocale(string $locale): string
    {
        return strtolower(explode('-', $locale)[0]);
    }
}