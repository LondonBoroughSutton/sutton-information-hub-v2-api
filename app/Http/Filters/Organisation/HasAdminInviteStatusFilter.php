<?php

namespace App\Http\Filters\Organisation;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class HasAdminInviteStatusFilter implements Filter
{
    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param $value
     * @param string $property
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function __invoke(Builder $query, $value, string $property): Builder
    {
        if ($value === 'confirmed') {
            return $query->hasAdmin();
        }
        if ($value === 'pending') {
            return $query->hasPendingAdminInvite();
        }
        if ($value === 'invited') {
            return $query->hasAdminInvite();
        }
        if ($value === 'none') {
            $query->hasNoAdmin()->hasNoPendingAdminInvite()->hasNoAdminInvite();
        }

        return $query;
    }
}
