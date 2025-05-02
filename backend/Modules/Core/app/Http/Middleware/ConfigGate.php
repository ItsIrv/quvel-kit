<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Modules\Core\Services\FrontendService;

class ConfigGate
{
    public function handle(Request $request, Closure $next, string $key, string $expected): Response
    {
        $actual = config($key);

        $expected = match (strtolower($expected)) {
            'true'  => true,
            'false' => false,
            'null'  => null,
            default => is_numeric($expected) ? +$expected : $expected,
        };

        if ($actual !== $expected) {
            return $this->denyResponse($request, $key);
        }

        return $next($request);
    }

    protected function denyResponse(Request $request, string $key): JsonResponse|RedirectResponse
    {
        $message = __('common::feature.status.info.notAvailable');

        return $request->wantsJson()
            ? new JsonResponse([
                'message' => $message,
            ], 403)
            : app(FrontendService::class)->redirect('', [
                'message' => $message,
            ]);
    }
}
