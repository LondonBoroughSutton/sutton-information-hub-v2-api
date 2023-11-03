<?php

namespace App\Http\Resources;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UpdateRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'actioning_user_id' => $this->actioning_user_id,
            'updateable_type' => $this->updateable_type,
            'updateable_id' => $this->updateable_id,
            'data' => $this->getUpdateable()->getData($this->data),
            'created_at' => $this->created_at->format(CarbonImmutable::ISO8601),
            'updated_at' => $this->updated_at->format(CarbonImmutable::ISO8601),
            'approved_at' => $this->approved_at?->format(CarbonImmutable::ISO8601),
            'deleted_at' => $this->deleted_at?->format(CarbonImmutable::ISO8601),

            // Relationships.
            'user' => new UserResource($this->whenLoaded('user')),

            // Appends.
            'entry' => $this->entry,
        ];
    }
}
