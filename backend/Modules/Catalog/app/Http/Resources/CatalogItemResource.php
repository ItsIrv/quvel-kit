<?php

namespace Modules\Catalog\Http\Resources;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property string $uuid
 * @property string $name
 * @property string $description
 * @property string $image
 * @property bool $is_public
 * @property array<string, mixed> $metadata
 * @property User $user
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class CatalogItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'description' => $this->description,
            'image' => $this->image,
            'is_public' => $this->is_public,

            // Only show specific fields to authenticated users
            'metadata' => $request->user()?->id !== null ? $this->metadata : [],
            'user' => $request->user()?->id !== null ? [
                'name' => $this->user->name,
            ] : null,

            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
