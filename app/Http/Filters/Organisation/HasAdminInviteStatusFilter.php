<?php

namespace App\Http\Filters\Organisation;

use App\Models\Organisation;
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
        if ($value === Organisation::ADMIN_INVITE_STATUS_CONFIRMED) {
            return $query->hasAdmin();
        }
        if ($value === Organisation::ADMIN_INVITE_STATUS_PENDING) {
            return $query->hasPendingAdminInvite();
        }
        if ($value === Organisation::ADMIN_INVITE_STATUS_INVITED) {
            return $query->hasAdminInvite();
        }
        if ($value === Organisation::ADMIN_INVITE_STATUS_NONE) {
            $query->hasNoAdmin()->hasNoPendingAdminInvite()->hasNoAdminInvite();
        }

        return $query;
    }
}
