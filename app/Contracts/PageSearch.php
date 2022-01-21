<?php

namespace App\Contracts;

use App\Support\Coordinate;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

interface PageSearch
{
    const ORDER_RELEVANCE = 'relevance';

    const ORDER_DISTANCE = 'distance';

    /**
     * @param string $term
     * @return \App\Contracts\PageSearch
     */
    public function applyQuery(string $term): PageSearch;

    /**
     * @param string $category
     * @return \App\Contracts\PageSearch
     */
    public function applyCategory(string $category): PageSearch;

    /**
     * @param string $persona
     * @return \App\Contracts\PageSearch
     */
    public function applyPersona(string $persona): PageSearch;

    /**
     * @param string $waitTime
     * @return \App\Contracts\PageSearch
     */
    public function applyWaitTime(string $waitTime): PageSearch;

    /**
     * @param bool $isFree
     * @return \App\Contracts\PageSearch
     */
    public function applyIsFree(bool $isFree): PageSearch;

    /**
     * @param string $order
     * @param \App\Support\Coordinate|null $location
     * @return \App\Contracts\PageSearch
     */
    public function applyOrder(string $order, Coordinate $location = null): PageSearch;

    /**
     * @param \App\Support\Coordinate $location
     * @param int $radius
     * @return \App\Contracts\PageSearch
     */
    public function applyRadius(Coordinate $location, int $radius): PageSearch;

    /**
     * Returns the underlying query. Only intended for use in testing.
     *
     * @return array
     */
    public function getQuery(): array;

    /**
     * @param int|null $page
     * @param int|null $perPage
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function paginate(int $page = null, int $perPage = null): AnonymousResourceCollection;

    /**
     * @param int|null $perPage
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function get(int $perPage = null): AnonymousResourceCollection;

    public function applyEligibilities(array $eligibilityNames): PageSearch;
}
