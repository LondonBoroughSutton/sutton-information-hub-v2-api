<?php

namespace App\Http\Controllers\Core\V1;

use App\Models\LocalAuthority;
use Spatie\QueryBuilder\Filter;
use App\Http\Controllers\Controller;
use Spatie\QueryBuilder\QueryBuilder;
use App\Http\Resources\LocalAuthorityResource;
use App\Http\Requests\LocalAuthority\IndexRequest;

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
