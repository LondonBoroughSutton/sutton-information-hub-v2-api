<?php

namespace App\Http\Filters\Organisation;

use App\Models\Organisation;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class HasEmailFilter implements Filter
{
    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param $value
     * @param string $property
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function __invoke(Builder $query, $value, string $property): Builder
    {
        $hasEmail = (bool)$value;

        return $hasEmail
            ? $query->whereNotNull(table(Organisation::class, 'email'))
            : $query->whereNull(table(Organisation::class, 'email'));
    }
}
