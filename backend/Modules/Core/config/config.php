<?php

use Modules\Core\Services\Security\GoogleRecaptchaVerifier;

return [
    /**
     * Recaptcha Configuration
     */
    'recaptcha' => [
        /**
         * The captcha verifier to use.
         */
        'provider' => GoogleRecaptchaVerifier::class,

        /**
         * Google recaptcha configuration.
         */
        'google'   => [
            'secret' => env('RECAPTCHA_SECRET'),
        ],
    ],
];
