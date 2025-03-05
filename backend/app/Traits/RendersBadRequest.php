<?php

namespace App\Traits;

use App\Contracts\TranslatableException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

trait RendersBadRequest
{
    /**
     * Render the exception into a JSON response.
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this instanceof TranslatableException
                ? $this->getTranslatedMessage()
                : $this->getMessage(),
        ], Response::HTTP_BAD_REQUEST);
    }
}
