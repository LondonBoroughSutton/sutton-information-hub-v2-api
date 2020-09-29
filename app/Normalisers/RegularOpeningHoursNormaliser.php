<?php

namespace App\Normalisers;

use App\Models\RegularOpeningHour;

class RegularOpeningHoursNormaliser
{
    /**
     * @param array $openingHours
     * @return array
     */
    public function normalise(array $openingHours): array
    {
        $normalised = [
            'frequency' => $openingHours['frequency'],
            'weekday' => $openingHours['weekday'] ?? null,
            'day_of_month' => $openingHours['day_of_month'] ?? null,
            'occurrence_of_month' => $openingHours['occurrence_of_month'] ?? null,
            'starts_at' => $openingHours['starts_at'] ?? null,
            'opens_at' => $openingHours['opens_at'],
            'closes_at' => $openingHours['closes_at'],
        ];

        if (!in_array($normalised['frequency'], [
            RegularOpeningHour::FREQUENCY_WEEKLY,
            RegularOpeningHour::FREQUENCY_NTH_OCCURRENCE_OF_MONTH,
        ])) {
            $normalised['weekday'] = null;
        }

        if ($openingHours['frequency'] !== RegularOpeningHour::FREQUENCY_MONTHLY) {
            $normalised['day_of_month'] = null;
        }

        if ($openingHours['frequency'] !== RegularOpeningHour::FREQUENCY_NTH_OCCURRENCE_OF_MONTH) {
            $normalised['occurrence_of_month'] = null;
        }

        if ($openingHours['frequency'] !== RegularOpeningHour::FREQUENCY_FORTNIGHTLY) {
            $normalised['starts_at'] = null;
        }

        return $normalised;
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
