<?php

namespace Modules\Core\Services\Security;

use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Http\Request;
use Modules\Core\Contracts\Security\CaptchaVerifierInterface;
use Modules\Tenant\Contexts\TenantContext;

class GoogleRecaptchaVerifier implements CaptchaVerifierInterface
{
    public function __construct(
        private readonly Request $request,
        private readonly HttpClient $http,
        private readonly TenantContext $tenantContext,
    ) {
    }

    public function verify(string $token, ?string $ip = null): bool
    {
        // Get secret key from tenant config
        $secretKey = $this->tenantContext->getConfigValue('recaptcha_secret_key');

        // If no secret key is configured, validation fails
        if (empty($secretKey)) {
            return false;
        }

        $response = $this->http->asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret'   => $secretKey,
            'response' => $token,
            'remoteip' => $ip ?? $this->request->ip(),
        ]);

        return $response->json('success') === true;
    }
}
