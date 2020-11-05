<?php

namespace App\Http\Filters\Organisation;

use App\Models\Organisation;
use App\Models\SocialMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\Filters\Filter;

class HasSocialMediasFilter implements Filter
{
    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param $value
     * @param string $property
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function __invoke(Builder $query, $value, string $property): Builder
    {
        switch ($value) {
            case 'any':
                $query = $query->whereHas('socialMedias');
                break;
            case 'none':
                $query = $query->whereDoesntHave('socialMedias');
                break;
            case SocialMedia::TYPE_FACEBOOK:
            case SocialMedia::TYPE_TWITTER:
            case SocialMedia::TYPE_INSTAGRAM:
            case SocialMedia::TYPE_YOUTUBE:
            case SocialMedia::TYPE_OTHER:
                $query = $query->whereExists(function ($query) use ($value) {
                    $query->select(DB::raw(1))
                        ->from('social_medias')
                        ->whereRaw('organisations.id = social_medias.sociable_id')
                        ->whereRaw('social_medias.sociable_type = ?', [table(Organisation::class)])
                        ->whereRaw('social_medias.type = ?', [$value]);
                });
                break;
        }

        return $query;
    }
}
