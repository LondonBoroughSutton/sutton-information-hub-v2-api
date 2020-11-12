<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ServiceCriterionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        /**
         * @todo attributes should be returned as an array, but to retain consistency in the api they are converted to strings
         * Once time is available to update the api clients this should be removed.
         */
        return [
            'age_group' => $this->age_group ? implode(', ', $this->age_group) : null,
            'disability' => $this->disability ? implode(', ', $this->disability) : null,
            'employment' => $this->employment ? implode(', ', $this->employment) : null,
            'gender' => $this->gender ? implode(', ', $this->gender) : null,
            'benefits' => $this->benefits ? implode(', ', $this->benefits) : null,
        ];
    }
}
