<?php

namespace App\Transformers;

use App\Models\Location;
use App\Models\SocialMedia;
use Illuminate\Support\Collection;

class OrganisationInviteTransformer
{
    /**
     * @param \App\Models\Location|null $location
     * @return string|null
     */
    public function transformAddress(?Location $location): ?string
    {
        if ($location === null) {
            return null;
        }

        $addressParts = [
            $location->address_line_1,
            $location->address_line_2,
            $location->address_line_3,
            $location->city,
            $location->county,
            $location->postcode,
            $location->country,
        ];

        $addressParts = array_filter($addressParts);

        return implode(', ', $addressParts);
    }

    /**
     * @param \Illuminate\Support\Collection|null $socialMedias
     * @return string|null
     */
    public function transformSocialMedias(?Collection $socialMedias): ?string
    {
        if ($socialMedias === null) {
            return null;
        }

        return $socialMedias
            ->map(function (SocialMedia $socialMedia): string {
                return $this->transformSocialMedia($socialMedia);
            })
            ->implode(', ');
    }

    /**
     * @param \App\Models\SocialMedia $socialMedia
     * @return string
     */
    protected function transformSocialMedia(SocialMedia $socialMedia): string
    {
        $type = $socialMedia->type;

        switch ($type) {
            case SocialMedia::TYPE_TWITTER:
                $type = 'Twitter';
                break;
            case SocialMedia::TYPE_FACEBOOK:
                $type = 'Facebook';
                break;
            case SocialMedia::TYPE_INSTAGRAM:
                $type = 'Instagram';
                break;
            case SocialMedia::TYPE_YOUTUBE:
                $type = 'YouTube';
                break;
            case SocialMedia::TYPE_OTHER:
                $type = 'Other';
                break;
        }

        return "{$type}: {$socialMedia->url}";
    }
}
