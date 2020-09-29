<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrganisationSignUpFormResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'user' => new UserResource($this->resource['user']),
            'organisation' => new OrganisationResource($this->resource['organisation']),
            'service' => new ServiceResource($this->resource['service']),
        ];
    }
}
