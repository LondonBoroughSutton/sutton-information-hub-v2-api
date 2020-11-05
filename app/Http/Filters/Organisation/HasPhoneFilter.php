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
        switch ($value) {
            case 'any':
                $query = $query->whereNotNull(table(Organisation::class, 'phone'));
                break;
            case 'none':
                $query = $query->whereNull(table(Organisation::class, 'phone'));
                break;
            case 'mobile':
                $query = $query->whereRaw('LEFT(' . table(Organisation::class, 'phone') . ',2) = "07"');
                break;
            case 'landline':
                $query = $query->whereRaw('LEFT(' . table(Organisation::class, 'phone') . ',2) <> "07"');
                break;
        }

        return $query;
    }
}
