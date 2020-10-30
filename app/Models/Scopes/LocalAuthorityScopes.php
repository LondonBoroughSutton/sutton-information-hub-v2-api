<?php

namespace App\Models\Scopes;

use App\Models\LocalAuthority;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

trait LocalAuthorityScopes
{
    /**
     * Filter Local Authorities by region.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $regionSlug
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRegion(Builder $query, $regionSlug): Builder
    {
        $regions = [
            LocalAuthority::REGION_ENGLAND,
            LocalAuthority::REGION_SCOTLAND,
            LocalAuthority::REGION_WALES,
            LocalAuthority::REGION_NORTHERN_IRELAND,
        ];
        foreach ($regions as $region) {
            if ($regionSlug === Str::slug($region)) {
                $regionChar = mb_strtoupper(mb_substr($region, 0, 1));
                $query->where('code', 'like', "{$regionChar}%");
                break;
            }
        }

        return $query;
    }
}
