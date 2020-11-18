<?php

namespace App\Http\Controllers\Core\V1;

use App\Events\EndpointHit;
use App\Generators\UniqueSlugGenerator;
use App\Http\Controllers\Controller;
use App\Http\Requests\Collection\Category\DestroyRequest;
use App\Http\Requests\Collection\Category\IndexRequest;
use App\Http\Requests\Collection\Category\ShowRequest;
use App\Http\Requests\Collection\Category\StoreRequest;
use App\Http\Requests\Collection\Category\UpdateRequest;
use App\Http\Resources\CollectionCategoryResource;
use App\Http\Responses\ResourceDeleted;
use App\Models\Collection;
use App\Models\File;
use App\Models\Taxonomy;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\Filter;
use Spatie\QueryBuilder\QueryBuilder;

class CollectionCategoryController extends Controller
{
    /**
     * CategoryController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api')->except('index', 'show');
    }

    /**
     * Display a listing of the resource.
     *
     * @param \App\Http\Requests\Collection\Category\IndexRequest $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(IndexRequest $request)
    {
        $baseQuery = Collection::categories()
            ->orderBy('order');

        $categories = QueryBuilder::for($baseQuery)
            ->allowedFilters([
                Filter::exact('id'),
            ])
            ->with('taxonomies')
            ->paginate(per_page($request->per_page));

        event(EndpointHit::onRead($request, 'Viewed all collection categories'));

        return CollectionCategoryResource::collection($categories);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Http\Requests\Collection\Category\StoreRequest $request
     * @param \App\Generators\UniqueSlugGenerator $slugGenerator
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request, UniqueSlugGenerator $slugGenerator)
    {
        return DB::transaction(function () use ($request, $slugGenerator) {
            // Parse the sideboxes.
            $sideboxes = array_map(function (array $sidebox): array {
                return [
                    'title' => $sidebox['title'],
                    'content' => sanitize_markdown($sidebox['content']),
                ];
            }, $request->sideboxes ?? []);

            // Create the collection record.
            $category = Collection::create([
                'type' => Collection::TYPE_CATEGORY,
                'slug' => $slugGenerator->generate($request->name, table(Collection::class)),
                'name' => $request->name,
                'meta' => [
                    'intro' => $request->intro,
                    'image_file_id' => $request->image_file_id,
                    'sideboxes' => $sideboxes,
                ],
                'order' => $request->order,
            ]);

            if ($request->filled('image_file_id')) {
                File::findOrFail($request->image_file_id)->assigned();
            }

            // Create all of the pivot records.
            $taxonomies = Taxonomy::whereIn('id', $request->category_taxonomies)->get();
            $category->syncCollectionTaxonomies($taxonomies);

            // Reload the newly created pivot records.
            $category->load('taxonomies');

            event(EndpointHit::onCreate($request, "Created collection category [{$category->id}]", $category));

            return new CollectionCategoryResource($category);
        });
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Http\Requests\Collection\Category\ShowRequest $request
     * @param \App\Models\Collection $collection
     * @return \App\Http\Resources\CollectionCategoryResource
     */
    public function show(ShowRequest $request, Collection $collection)
    {
        $baseQuery = Collection::query()
            ->where('id', $collection->id);

        $collection = QueryBuilder::for($baseQuery)
            ->firstOrFail();

        event(EndpointHit::onRead($request, "Viewed collection category [{$collection->id}]", $collection));

        return new CollectionCategoryResource($collection);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Http\Requests\Collection\Category\UpdateRequest $request
     * @param \App\Generators\UniqueSlugGenerator $slugGenerator
     * @param \App\Models\Collection $collection
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRequest $request, UniqueSlugGenerator $slugGenerator, Collection $collection)
    {
        return DB::transaction(function () use ($request, $slugGenerator, $collection) {
            // Parse the sideboxes.
            $sideboxes = array_map(function (array $sidebox): array {
                return [
                    'title' => $sidebox['title'],
                    'content' => sanitize_markdown($sidebox['content']),
                ];
            }, $request->sideboxes ?? []);

            // Update the collection record.
            $collection->update([
                'slug' => $slugGenerator->compareEquals($request->name, $collection->slug)
                    ? $collection->slug
                    : $slugGenerator->generate($request->name, table(Collection::class)),
                'name' => $request->name,
                'meta' => [
                    'intro' => $request->intro,
                    'image_file_id' => $request->has('image_file_id')
                        ? $request->image_file_id
                        : ($collection->meta['image_file_id']?? null),
                    'sideboxes' => $sideboxes,
                ],
                'order' => $request->order,
            ]);

            if ($request->filled('image_file_id')) {
                File::findOrFail($request->image_file_id)->assigned();
            }

            // Update or create all of the pivot records.
            $taxonomies = Taxonomy::whereIn('id', $request->category_taxonomies)->get();
            $collection->syncCollectionTaxonomies($taxonomies);

            event(EndpointHit::onUpdate($request, "Updated collection category [{$collection->id}]", $collection));

            return new CollectionCategoryResource($collection);
        });
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Http\Requests\Collection\Category\DestroyRequest $request
     * @param \App\Models\Collection $collection
     * @return \Illuminate\Http\Response
     */
    public function destroy(DestroyRequest $request, Collection $collection)
    {
        return DB::transaction(function () use ($request, $collection) {
            event(EndpointHit::onDelete($request, "Deleted collection category [{$collection->id}]", $collection));

            $collection->delete();

            return new ResourceDeleted('collection category');
        });
    }
}
