<?php

namespace App\Http\Filters\Organisation;

use App\Models\Organisation;
use App\Models\Role;
use App\Models\UserRole;
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
            $organisationIds = $user->organisations()
                ->join(table(Role::class), function ($join) {
                    $join->on(table(UserRole::class, 'role_id'), '=', table(Role::class, 'id'))
                        ->whereIn(table(Role::class, 'name'), [Role::NAME_ORGANISATION_ADMIN, Role::NAME_GLOBAL_ADMIN, Role::NAME_SUPER_ADMIN]);
                })
                ->pluck(table(Organisation::class, 'id'))
                ->toArray();
        }

        return $query->whereIn(table(Organisation::class, 'id'), $organisationIds);
    }
}
