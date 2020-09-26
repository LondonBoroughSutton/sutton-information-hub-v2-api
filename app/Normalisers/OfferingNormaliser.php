<?php

namespace App\Normalisers;

class OfferingNormaliser
{
    /**
     * @param array $offering
     * @return array
     */
    public function normalise(array $offering): array
    {
        return [
            'offering' => $offering['offering'],
            'order' => $offering['order'],
        ];
    }

    /**
     * @param array $multipleOfferings
     * @return array
     */
    public function normaliseMultiple(array $multipleOfferings): array
    {
        return array_map(function (array $offering): array {
            return $this->normalise($offering);
        }, $multipleOfferings);
    }
}
