<?php

declare(strict_types=1);

namespace Quvel\Core\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Trait for exceptions that need to render JSON error responses.
 * @method getMessage()
 */
trait RendersJsonError
{
    /**
     * Render the exception as a JSON response.
     */
    public function render(): JsonResponse
    {
        $statusCode = $this->getStatusCode();
        $message = $this->getJsonMessage();

        $data = [
            'message' => $message,
            'error' => true,
        ];

        if (method_exists($this, 'getContext')) {
            $context = $this->getContext();

            if (!empty($context)) {
                $data['context'] = $context;
            }
        }

        if (method_exists($this, 'getErrorCode')) {
            $errorCode = $this->getErrorCode();

            if ($errorCode) {
                $data['code'] = $errorCode;
            }
        }

        return response()->json($data, $statusCode);
    }

    /**
     * Get the HTTP status code for this exception.
     * Override this method to customize the status code.
     */
    protected function getStatusCode(): int
    {
        return property_exists($this, 'statusCode') ? $this->statusCode : 400;
    }

    /**
     * Get the message to include in the JSON response.
     * Uses the Laravel translation system if the message is a translation key.
     */
    protected function getJsonMessage(): string
    {
        $message = $this->getMessage();

        if (function_exists('__') && str_contains($message, '.')) {
            $translation = __($message);

            if (is_string($translation) && $translation !== $message) {
                return $translation;
            }
        }

        return $message;
    }
}