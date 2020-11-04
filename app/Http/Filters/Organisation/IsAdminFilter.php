<?php

namespace App\Http\Filters\Organisation;

use App\Models\Organisation;
use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class IsAdminFilter implements Filter
{
    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param $value
     * @param string $property
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function __invoke(Builder $query, $value, string $property): Builder
    {
        $organisationIds = [];
        $user = request()->user('api');

        if ($user) {
            if ($user->isGlobalAdmin()) {
                $organisationIds = Organisation::query()
                    ->pluck(table(Organisation::class, 'id'))
                    ->toArray();
            } else {
                $organisationIds = $user->organisations()
                    ->wherePivot('role_id', '=', Role::organisationAdmin()->id)
                    ->pluck(table(Organisation::class, 'id'))
                    ->toArray();
            }
        }

        return $query->whereIn(table(Organisation::class, 'id'), $organisationIds);
    }
}
