<?php

namespace Modules\Core\Http\Actions\Debug;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Services\FrontendService;

/**
 * Debug action that displays proxy and request information.
 *
 * This endpoint is only accessible when APP_DEBUG is true.
 */
class ShowProxyInfoAction
{
    /**
     * Create a new action instance.
     */
    public function __construct(
        private readonly FrontendService $frontendService,
        private readonly Config $config,
    ) {
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        // Only show this endpoint when APP_DEBUG is true
        if (!((bool) $this->config->get('app.debug'))) {
            abort(404);
        }

        return response()->json([
            'environment'       => [
                'APP_DEBUG'         => $this->config->get('app.debug'),
                'APP_ENV'           => $this->config->get('app.env'),
                'TRUST_PROXIES'     => $this->config->get('trustedproxy.proxies'),
                'TRUSTED_PROXY_IPS' => $this->config->get('trustedproxy.headers'),
                'OCTANE_SERVER'     => $this->config->get('octane.server'),
            ],
            'request_info'      => [
                'method'      => $request->method(),
                'url'         => $request->url(),
                'full_url'    => $request->fullUrl(),
                'scheme'      => $request->getScheme(),
                'host'        => $request->getHost(),
                'port'        => $request->getPort(),
                'path'        => $request->path(),
                'is_secure'   => $request->isSecure(),
                'client_ip'   => $request->getClientIp(),
                'remote_addr' => $request->server('REMOTE_ADDR'),
            ],
            'proxy_detection'   => [
                'is_from_trusted_proxy' => $request->isFromTrustedProxy(),
                /** @phpstan-ignore-next-line staticMethod.dynamicCall */
                'trusted_proxies'       => $request->getTrustedProxies(),
            ],
            'forwarded_headers' => [
                'x_forwarded_for'    => $request->header('X-Forwarded-For'),
                'x_forwarded_host'   => $request->header('X-Forwarded-Host'),
                'x_forwarded_proto'  => $request->header('X-Forwarded-Proto'),
                'x_forwarded_port'   => $request->header('X-Forwarded-Port'),
                'x_forwarded_prefix' => $request->header('X-Forwarded-Prefix'),
                'x_real_ip'          => $request->header('X-Real-IP'),
            ],
            'server_variables'  => [
                'HTTPS'          => $request->server('HTTPS'),
                'SERVER_PORT'    => $request->server('SERVER_PORT'),
                'SERVER_NAME'    => $request->server('SERVER_NAME'),
                'HTTP_HOST'      => $request->server('HTTP_HOST'),
                'REQUEST_SCHEME' => $request->server('REQUEST_SCHEME'),
                'REQUEST_URI'    => $request->server('REQUEST_URI'),
            ],
            'url_generation'    => [
                'app_url'      => $this->config->get('app.url'),
                'url_helper'   => url('/'),
                'asset_helper' => asset('test.css'),
                /** @phpstan-ignore-next-line nullCoalesce.expr */
                'route_helper' => route('login') ?? 'No login route found',
            ],
            'all_headers'       => $request->headers->all(),
            'timestamp'         => now()->toISOString(),
            'frontend_url'      => $this->frontendService->getUrl(),
        ]);
    }
}
