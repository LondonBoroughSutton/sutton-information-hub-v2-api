<?php

namespace App\Normalisers;

class HolidayOpeningHoursNormaliser
{
    /**
     * @param array $openingHours
     * @return array
     */
    public function normalise(array $openingHours): array
    {
        return [
            'is_closed' => $openingHours['is_closed'],
            'starts_at' => $openingHours['starts_at'],
            'ends_at' => $openingHours['ends_at'],
            'opens_at' => $openingHours['opens_at'],
            'closes_at' => $openingHours['closes_at'],
        ];
    }

    /**
     * @param array $multipleOpeningHours
     * @return array
     */
    public function normaliseMultiple(array $multipleOpeningHours): array
    {
        return array_map(function (array $openingHours): array {
            return $this->normalise($openingHours);
        }, $multipleOpeningHours);
    }
}
