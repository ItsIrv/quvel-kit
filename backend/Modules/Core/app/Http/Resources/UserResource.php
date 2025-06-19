<?php

namespace Modules\Core\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * User resource.
 *
 * @property string $id
 * @property string $public_id
 * @property string $name
 * @property string $email
 * @property string $avatar
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->public_id,
            'name'       => $this->name,
            'email'      => $this->email,
            'avatar'     => $this->avatar,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
