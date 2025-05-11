<?php

namespace Modules\Core\Http\Middleware\Lang;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Http\Request;

/**
 * Middleware to set the application locale from the Accept-Language header,
 * verifying against allowed locales from config.
 */
class SetRequestLocaleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $allowedLocales = config('localization.allowed_locales', ['en']);
        $defaultLocale  = config('app.locale', 'en');
        $header         = $request->header('Accept-Language');

        if ($header) {
            $locales = $this->parseAcceptLanguage($header);
            foreach ($locales as $locale) {
                $normalized = $this->normalizeLocale($locale);
                if (in_array($normalized, $allowedLocales, true)) {
                    App::setLocale($normalized);
                    break;
                }
            }
        } else {
            app()->setLocale($defaultLocale);
        }

        return $next($request);
    }

    /**
     * Parse the Accept-Language header into an array of language codes.
     */
    protected function parseAcceptLanguage(string $header): array
    {
        // Parse and sort by q-value, return array of codes
    }

    /**
     * Normalize a locale code (e.g., 'es-MX' => 'es').
     */
    protected function normalizeLocale(string $locale): string
    {
        return strtolower(explode('-', $locale)[0]);
    }
}
