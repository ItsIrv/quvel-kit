<?php

namespace App\Traits;

use Modules\Core\Contracts\TranslatableEntity;
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
            'message' => $this instanceof TranslatableEntity
                ? $this->getTranslatedMessage()
                : $this->getMessage(),
        ], Response::HTTP_BAD_REQUEST);
    }
}
