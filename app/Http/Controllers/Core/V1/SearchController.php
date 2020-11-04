<?php

namespace App\Http\Controllers\Core\V1;

use App\Contracts\Search;
use App\Http\Controllers\Controller;
use App\Http\Requests\Search\Request;
use App\Support\Coordinate;

class SearchController extends Controller
{
    /**
     * @param \App\Contracts\Search $search
     * @param \App\Http\Requests\Search\Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function __invoke(Search $search, Request $request)
    {
        // Apply query.
        if ($request->has('query')) {
            $search->applyQuery($request->input('query'));
        }

        // Apply filter on `type` field.
        if ($request->has('type')) {
            $search->applyType($request->input('type'));
        }

        if ($request->has('category')) {
            // If category given then filter by category.
            foreach (explode(',', $request->category) as $category) {
                $search->applyCategory($category);
            }
        } elseif ($request->has('persona')) {
            // Otherwise, if persona given then filter by persona.
            foreach (explode(',', $request->persona) as $persona) {
                $search->applyPersona($persona);
            }
        }

        // Apply filter on `wait_time` field.
        if ($request->has('wait_time')) {
            $search->applyWaitTime($request->wait_time);
        }

        // Apply filter on `is_free` field.
        if ($request->has('is_free')) {
            $search->applyIsFree($request->is_free);
        }

        // If location was passed, then parse the location.
        if ($request->has('location')) {
            $location = new Coordinate(
                $request->input('location.lat'),
                $request->input('location.lon')
            );

            // Apply radius filtering.
            $search->applyRadius($location, $request->radius ?? config('hlp.search_distance'));
        } elseif ($request->has('is_national')) {
            // Apply filter on `is_national` field.
            $search->applyIsNational((bool)$request->is_national);
        }

        // Apply order.
        $search->applyOrder($request->order ?? 'relevance', $location ?? null);

        // dd($search->getQuery());

        // Perform the search.
        return $search->paginate($request->page, $request->per_page);
    }
}
