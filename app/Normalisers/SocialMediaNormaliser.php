<?php

namespace App\Normalisers;

class SocialMediaNormaliser
{
    /**
     * @param array $socialMedia
     * @return array
     */
    public function normalise(array $socialMedia): array
    {
        return [
            'type' => $socialMedia['type'],
            'url' => $socialMedia['url'],
        ];
    }

    /**
     * @param array $multipleSocialMedias
     * @return array
     */
    public function normaliseMultiple(array $multipleSocialMedias): array
    {
        return array_map(function (array $socialMedia): array {
            return $this->normalise($socialMedia);
        }, $multipleSocialMedias);
    }
}
