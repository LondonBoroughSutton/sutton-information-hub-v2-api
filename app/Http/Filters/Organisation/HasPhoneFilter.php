<?php

namespace App\Http\Filters\Organisation;

use App\Models\Organisation;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class HasPhoneFilter implements Filter
{
    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param $value
     * @param string $property
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function __invoke(Builder $query, $value, string $property): Builder
    {
        $hasPhone = (bool)$value;

        return $hasPhone
            ? $query->whereNotNull(table(Organisation::class, 'phone'))
            : $query->whereNull(table(Organisation::class, 'phone'));
    }
}
