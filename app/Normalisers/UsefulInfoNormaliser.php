<?php

namespace App\Normalisers;

class UsefulInfoNormaliser
{
    /**
     * @param array $usefulInfo
     * @return array
     */
    public function normalise(array $usefulInfo): array
    {
        return [
            'title' => $usefulInfo['title'],
            'description' => sanitize_markdown($usefulInfo['description']),
            'order' => $usefulInfo['order'],
        ];
    }

    /**
     * @param array $multipleUsefulInfos
     * @return array
     */
    public function normaliseMultiple(array $multipleUsefulInfos): array
    {
        return array_map(function (array $usefulInfo): array {
            return $this->normalise($usefulInfo);
        }, $multipleUsefulInfos);
    }
}
