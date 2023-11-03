<?php

namespace App\Http\Resources;

use App\Models\RegularOpeningHour;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegularOpeningHourResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'frequency' => $this->frequency,
            'weekday' => $this->when(
                in_array(
                    $this->frequency,
                    [
                        RegularOpeningHour::FREQUENCY_WEEKLY,
                        RegularOpeningHour::FREQUENCY_NTH_OCCURRENCE_OF_MONTH,
                    ]
                ),
                $this->weekday
            ),
            'day_of_month' => $this->when($this->frequency === RegularOpeningHour::FREQUENCY_MONTHLY, $this->day_of_month),
            'occurrence_of_month' => $this->when($this->frequency === RegularOpeningHour::FREQUENCY_NTH_OCCURRENCE_OF_MONTH, $this->occurrence_of_month),
            'starts_at' => $this->when($this->frequency === RegularOpeningHour::FREQUENCY_FORTNIGHTLY, $this->starts_at?->toDateString()),
            'opens_at' => $this->opens_at->toString(),
            'closes_at' => $this->closes_at->toString(),
        ];
    }
}
