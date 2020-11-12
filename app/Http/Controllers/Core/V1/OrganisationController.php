<?php

namespace App\Http\Controllers\Core\V1;

use App\Events\EndpointHit;
use App\Http\Controllers\Controller;
use App\Http\Filters\Organisation\HasAdminInviteStatusFilter;
use App\Http\Filters\Organisation\HasEmailFilter;
use App\Http\Filters\Organisation\HasPermissionFilter;
use App\Http\Filters\Organisation\HasPhoneFilter;
use App\Http\Filters\Organisation\HasServicesFilter;
use App\Http\Filters\Organisation\HasSocialMediasFilter;
use App\Http\Filters\Organisation\IsAdminFilter;
use App\Http\Requests\Organisation\DestroyRequest;
use App\Http\Requests\Organisation\IndexRequest;
use App\Http\Requests\Organisation\ShowRequest;
use App\Http\Requests\Organisation\StoreRequest;
use App\Http\Requests\Organisation\UpdateRequest;
use App\Http\Resources\OrganisationResource;
use App\Http\Responses\ResourceDeleted;
use App\Models\File;
use App\Models\Organisation;
use App\Normalisers\SocialMediaNormaliser;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\Filter;
use Spatie\QueryBuilder\QueryBuilder;

class OrganisationController extends Controller
{
    /**
     * OrganisationController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api')->except('index', 'show');
    }

    /**
     * Display a listing of the resource.
     *
     * @param \App\Http\Requests\Organisation\IndexRequest $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(IndexRequest $request)
    {
        $baseQuery = Organisation::select(table(Organisation::class, '*'));

        $organisations = QueryBuilder::for($baseQuery)
            ->allowedFilters([
                Filter::exact('id'),
                'name',
                Filter::custom('is_admin', IsAdminFilter::class),
                Filter::custom('has_permission', HasPermissionFilter::class),
                Filter::custom('has_email', HasEmailFilter::class),
                Filter::custom('has_social_medias', HasSocialMediasFilter::class),
                Filter::custom('has_phone', HasPhoneFilter::class),
                Filter::custom('has_services', HasServicesFilter::class),
                Filter::custom('has_admin_invite_status', HasAdminInviteStatusFilter::class),
            ])
            ->allowedSorts('name')
            ->defaultSort('name')
            ->with(['location', 'socialMedias'])
            ->paginate(per_page($request->per_page));

        event(EndpointHit::onRead($request, 'Viewed all organisations'));

        return OrganisationResource::collection($organisations);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Http\Requests\Organisation\StoreRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request)
    {
        return DB::transaction(function () use ($request) {
            // Create the organisation.
            $organisation = Organisation::create([
                'slug' => $request->slug,
                'name' => $request->name,
                'description' => $request->description ? sanitize_markdown($request->description) : null,
                'url' => $request->url,
                'email' => $request->email,
                'phone' => $request->phone,
                'logo_file_id' => $request->logo_file_id,
                'location_id' => $request->location_id,
            ]);

            if ($request->filled('logo_file_id')) {
                /** @var \App\Models\File $file */
                $file = File::findOrFail($request->logo_file_id)->assigned();

                // Create resized version for common dimensions.
                foreach (config('hlp.cached_image_dimensions') as $maxDimension) {
                    $file->resizedVersion($maxDimension);
                }
            }

            if ($request->filled('social_medias')) {
                // Create the social media records.
                $social = [];
                foreach ($request->social_medias as $socialMedia) {
                    $social[] = [
                        'type' => $socialMedia['type'],
                        'url' => $socialMedia['url'],
                    ];
                }
                $organisation->socialMedias()->createMany($social);
            }

            event(EndpointHit::onCreate($request, "Created organisation [{$organisation->id}]", $organisation));

            return new OrganisationResource($organisation);
        });
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Http\Requests\Organisation\ShowRequest $request
     * @param \App\Models\Organisation $organisation
     * @return \App\Http\Resources\OrganisationResource
     */
    public function show(ShowRequest $request, Organisation $organisation)
    {
        $baseQuery = Organisation::query()
            ->where('id', $organisation->id);

        $organisation = QueryBuilder::for($baseQuery)
            ->firstOrFail();

        $organisation->load('location', 'socialMedias');

        event(EndpointHit::onRead($request, "Viewed organisation [{$organisation->id}]", $organisation));

        return new OrganisationResource($organisation);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Http\Requests\Organisation\UpdateRequest $request
     * @param \App\Normalisers\SocialMediaNormaliser $socialMediaNormaliser
     * @param \App\Models\Organisation $organisation
     * @return \Illuminate\Http\Response
     */
    public function update(
        UpdateRequest $request,
        SocialMediaNormaliser $socialMediaNormaliser,
        Organisation $organisation
    ) {
        return DB::transaction(function () use ($request, $socialMediaNormaliser, $organisation) {
            $organisation->update([
                'slug' => $request->input('slug', $organisation->slug),
                'name' => $request->input('name', $organisation->name),
                'description' => $request->input('description', $organisation->description),
                'url' => $request->input('url', $organisation->url),
                'email' => $request->input('email', $organisation->email),
                'phone' => $request->input('phone', $organisation->phone),
                'logo_file_id' => $request->input('logo_file_id', $organisation->logo_file_id),
                'location_id' => $request->input('location_id', $organisation->location_id),
            ]);

            // Update the social media records.
            if ($request->has('social_medias')) {
                $organisation->socialMedias()->delete();
                $socialMedias = $socialMediaNormaliser->normaliseMultiple(
                    $request->input('social_medias')
                );
                $organisation->socialMedias()->createMany($socialMedias);
            }

            if ($request->filled('logo_file_id')) {
                /** @var \App\Models\File $file */
                $file = File::findOrFail($request->logo_file_id)->assigned();

                // Create resized version for common dimensions.
                foreach (config('hlp.cached_image_dimensions') as $maxDimension) {
                    $file->resizedVersion($maxDimension);
                }
            }

            event(EndpointHit::onUpdate($request, "Updated organisation [{$organisation->id}]", $organisation));

            return new OrganisationResource($organisation);
        });
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Http\Requests\Organisation\DestroyRequest $request
     * @param \App\Models\Organisation $organisation
     * @return \Illuminate\Http\Response
     */
    public function destroy(DestroyRequest $request, Organisation $organisation)
    {
        return DB::transaction(function () use ($request, $organisation) {
            event(EndpointHit::onDelete($request, "Deleted organisation [{$organisation->id}]", $organisation));

            $organisation->delete();

            return new ResourceDeleted('organisation');
        });
    }
}
