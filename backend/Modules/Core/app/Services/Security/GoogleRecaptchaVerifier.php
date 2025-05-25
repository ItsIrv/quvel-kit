<?php

namespace Modules\Core\Services\Security;

use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Http\Request;
use Modules\Core\Contracts\Security\CaptchaVerifierInterface;

class GoogleRecaptchaVerifier implements CaptchaVerifierInterface
{
    private readonly string $secretKey;

    public function __construct(
        private readonly Request $request,
        private readonly HttpClient $http,
    ) {
        $this->secretKey = config('core.recaptcha.google.secret');
    }

    public function verify(string $token, ?string $ip = null): bool
    {
        $response = $this->http->asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret'   => $this->secretKey,
            'response' => $token,
            'remoteip' => $ip ?? $this->request->ip(),
        ]);

        return $response->json('success') === true;
    }
}
