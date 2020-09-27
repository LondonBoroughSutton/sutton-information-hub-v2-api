<?php

namespace App\Http\Filters\Service;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class HasCategoryTaxonomiesFilter implements Filter
{
    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param $value
     * @param string $property
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function __invoke(Builder $query, $value, string $property): Builder
    {
        $hasCategoryTaxonomies = (bool)$value;

        return $hasCategoryTaxonomies
            ? $query->whereHas('serviceTaxonomies')
            : $query->whereDoesntHave('serviceTaxonomies');
    }
}
