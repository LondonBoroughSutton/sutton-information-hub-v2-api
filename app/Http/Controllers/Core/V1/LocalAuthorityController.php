<?php

namespace App\Http\Controllers\Core\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LocalAuthority\IndexRequest;
use App\Http\Resources\LocalAuthorityResource;
use App\Models\LocalAuthority;
use Spatie\QueryBuilder\Filter;
use Spatie\QueryBuilder\QueryBuilder;

class LocalAuthorityController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param \App\Http\Requests\LocalAuthority\IndexRequest $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(IndexRequest $request)
    {
        $baseQuery = LocalAuthority::query();

        $localAuthorities = QueryBuilder::for($baseQuery)
            ->allowedFilters([
                Filter::scope('region'),
            ])
            ->defaultSort('name')
            ->get();

        return LocalAuthorityResource::collection($localAuthorities);
    }
}
