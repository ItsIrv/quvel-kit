<?php

namespace Modules\Core\Http\Middleware\Lang;

use Closure;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Modules\Core\Enums\CoreHeader;

/**
 * Middleware to set the application locale from the Accept-Language header,
 * verifying against allowed locales from config.
 */
class SetRequestLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $allowedLocales = app(ConfigRepository::class)->get('frontend.allowed_locales', ['en-US']);
        $header         = $request->header(CoreHeader::ACCEPT_LANGUAGE->value);

        if ($header !== null && in_array($header, $allowedLocales, true)) {
            app(Application::class)->setLocale(
                $this->normalizeLocale($header),
            );

            $request->setLocale($this->normalizeLocale($header));
        }

        return $next($request);
    }

    private function normalizeLocale(string $locale): string
    {
        return strtolower(explode('-', $locale)[0]);
    }
}
