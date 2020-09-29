<?php

namespace App\Http\Filters\Organisation;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class HasServicesFilter implements Filter
{
    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param $value
     * @param string $property
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function __invoke(Builder $query, $value, string $property): Builder
    {
        $hasServices = (bool)$value;

        return $hasServices
            ? $query->whereHas('services')
            : $query->whereDoesntHave('services');
    }
}
