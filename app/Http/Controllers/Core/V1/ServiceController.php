<?php

namespace App\Http\Controllers\Core\V1;

use App\Events\EndpointHit;
use App\Http\Controllers\Controller;
use App\Http\Filters\Service\HasCategoryTaxonomiesFilter;
use App\Http\Filters\Service\HasPermissionFilter;
use App\Http\Filters\Service\OrganisationNameFilter;
use App\Http\Requests\Service\DestroyRequest;
use App\Http\Requests\Service\IndexRequest;
use App\Http\Requests\Service\ShowRequest;
use App\Http\Requests\Service\StoreRequest;
use App\Http\Requests\Service\UpdateRequest;
use App\Http\Resources\ServiceResource;
use App\Http\Responses\ResourceDeleted;
use App\Http\Sorts\Service\OrganisationNameSort;
use App\Models\File;
use App\Models\Service;
use App\Models\Taxonomy;
use App\Normalisers\GalleryItemNormaliser;
use App\Normalisers\OfferingNormaliser;
use App\Normalisers\SocialMediaNormaliser;
use App\Normalisers\UsefulInfoNormaliser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\Filter;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\Sort;

class ServiceController extends Controller
{
    /**
     * ServiceController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api')->except('index', 'show');
    }

    /**
     * Display a listing of the resource.
     *
     * @param \App\Http\Requests\Service\IndexRequest $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(IndexRequest $request)
    {
        $baseQuery = Service::query()
            ->with(
                'serviceCriterion',
                'usefulInfos',
                'offerings',
                'socialMedias',
                'serviceGalleryItems.file',
                'taxonomies'
            )
            ->when(auth('api')->guest(), function (Builder $query) use ($request) {
                // Limit to active services if requesting user is not authenticated.
                $query->where('status', '=', Service::STATUS_ACTIVE);
            });

        $services = QueryBuilder::for($baseQuery)
            ->allowedFilters([
                Filter::exact('id'),
                Filter::exact('organisation_id'),
                'name',
                Filter::custom('organisation_name', OrganisationNameFilter::class),
                Filter::exact('status'),
                Filter::exact('referral_method'),
                Filter::exact('is_national'),
                Filter::custom('has_permission', HasPermissionFilter::class),
                Filter::custom('has_category_taxonomies', HasCategoryTaxonomiesFilter::class),
            ])
            ->allowedIncludes(['organisation'])
            ->allowedSorts([
                'name',
                Sort::custom('organisation_name', OrganisationNameSort::class),
                'status',
                'referral_method',
                'score'
            ])
            ->defaultSort('name')
            ->paginate(per_page($request->per_page));

        event(EndpointHit::onRead($request, 'Viewed all services'));

        return ServiceResource::collection($services);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Http\Requests\Service\StoreRequest $request
     * @param \App\Normalisers\UsefulInfoNormaliser $usefulInfoNormaliser
     * @param \App\Normalisers\OfferingNormaliser $offeringNormaliser
     * @param \App\Normalisers\SocialMediaNormaliser $socialMediaNormaliser
     * @param \App\Normalisers\GalleryItemNormaliser $galleryItemNormaliser
     * @return \Illuminate\Http\Response
     */
    public function store(
        StoreRequest $request,
        UsefulInfoNormaliser $usefulInfoNormaliser,
        OfferingNormaliser $offeringNormaliser,
        SocialMediaNormaliser $socialMediaNormaliser,
        GalleryItemNormaliser $galleryItemNormaliser
    ) {
        return DB::transaction(function () use (
            $request,
            $usefulInfoNormaliser,
            $offeringNormaliser,
            $socialMediaNormaliser,
            $galleryItemNormaliser
        ) {
            // Create the service record.
            /** @var \App\Models\Service $service */
            $service = Service::create([
                'organisation_id' => $request->organisation_id,
                'slug' => $request->slug,
                'name' => $request->name,
                'type' => $request->type,
                'status' => $request->status,
                'is_national' => $request->is_national,
                'intro' => $request->intro,
                'description' => sanitize_markdown($request->description),
                'wait_time' => $request->wait_time,
                'is_free' => $request->is_free,
                'fees_text' => $request->fees_text,
                'fees_url' => $request->fees_url,
                'testimonial' => $request->testimonial,
                'video_embed' => $request->video_embed,
                'url' => $request->url,
                'ios_app_url' => $request->ios_app_url,
                'android_app_url' => $request->android_app_url,
                'contact_name' => $request->contact_name,
                'contact_phone' => $request->contact_phone,
                'contact_email' => $request->contact_email,
                'show_referral_disclaimer' => $request->show_referral_disclaimer,
                'referral_method' => $request->referral_method,
                'referral_button_text' => $request->referral_button_text,
                'referral_email' => $request->referral_email,
                'referral_url' => $request->referral_url,
                'logo_file_id' => $request->logo_file_id,
                'score' => $request->score,
                'last_modified_at' => Date::now(),
            ]);

            // Create the service criterion record.
            $service->serviceCriterion()->create([
                'age_group' => $request->criteria['age_group'],
                'disability' => $request->criteria['disability'],
                'employment' => $request->criteria['employment'],
                'gender' => $request->criteria['gender'],
                'housing' => $request->criteria['housing'],
                'income' => $request->criteria['income'],
                'language' => $request->criteria['language'],
                'other' => $request->criteria['other'],
            ]);

            // Create the useful info records.
            $usefulInfos = $usefulInfoNormaliser->normaliseMultiple($request->useful_infos);
            $service->usefulInfos()->createMany($usefulInfos);

            // Create the offering records.
            $offerings = $offeringNormaliser->normaliseMultiple($request->offerings);
            $service->offerings()->createMany($offerings);

            // Create the social media records.
            $socialMedias = $socialMediaNormaliser->normaliseMultiple($request->social_medias);
            $service->socialMedias()->createMany($socialMedias);

            // Create the gallery item records.
            $galleryItems = $galleryItemNormaliser->normaliseMultiple($request->gallery_items);
            $service->serviceGalleryItems()->createMany($galleryItems);

            // Create the category taxonomy records.
            $taxonomies = Taxonomy::whereIn('id', $request->category_taxonomies)->get();
            $service->syncServiceTaxonomies($taxonomies);

            // Ensure conditional fields are reset if needed.
            $service->resetConditionalFields();

            if ($request->filled('gallery_items')) {
                foreach ($request->gallery_items as $galleryItem) {
                    /** @var \App\Models\File $file */
                    $file = File::findOrFail($galleryItem['file_id'])->assigned();

                    // Create resized version for common dimensions.
                    foreach (config('hlp.cached_image_dimensions') as $maxDimension) {
                        $file->resizedVersion($maxDimension);
                    }
                }
            }

            if ($request->filled('logo_file_id')) {
                /** @var \App\Models\File $file */
                $file = File::findOrFail($request->logo_file_id)->assigned();

                // Create resized version for common dimensions.
                foreach (config('hlp.cached_image_dimensions') as $maxDimension) {
                    $file->resizedVersion($maxDimension);
                }
            }

            event(EndpointHit::onCreate($request, "Created service [{$service->id}]", $service));

            $service->load('usefulInfos', 'offerings', 'socialMedias', 'taxonomies');

            return new ServiceResource($service);
        });
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Http\Requests\Service\ShowRequest $request
     * @param \App\Models\Service $service
     * @return \App\Http\Resources\ServiceResource
     */
    public function show(ShowRequest $request, Service $service)
    {
        $baseQuery = Service::query()
            ->with(
                'serviceCriterion',
                'usefulInfos',
                'offerings',
                'socialMedias',
                'serviceGalleryItems.file',
                'taxonomies'
            )
            ->where('id', $service->id);

        $service = QueryBuilder::for($baseQuery)
            ->allowedIncludes(['organisation'])
            ->firstOrFail();

        event(EndpointHit::onRead($request, "Viewed service [{$service->id}]", $service));

        return new ServiceResource($service);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Http\Requests\Service\UpdateRequest $request
     * @param \App\Normalisers\UsefulInfoNormaliser $usefulInfoNormaliser
     * @param \App\Normalisers\OfferingNormaliser $offeringNormaliser
     * @param \App\Normalisers\SocialMediaNormaliser $socialMediaNormaliser
     * @param \App\Normalisers\GalleryItemNormaliser $galleryItemNormaliser
     * @param \App\Models\Service $service
     * @return \Illuminate\Http\Response
     */
    public function update(
        UpdateRequest $request,
        UsefulInfoNormaliser $usefulInfoNormaliser,
        OfferingNormaliser $offeringNormaliser,
        SocialMediaNormaliser $socialMediaNormaliser,
        GalleryItemNormaliser $galleryItemNormaliser,
        Service $service
    ) {
        return DB::transaction(function () use (
            $request,
            $usefulInfoNormaliser,
            $offeringNormaliser,
            $socialMediaNormaliser,
            $galleryItemNormaliser,
            $service
        ) {
            $service->update([
                'organisation_id' => $request->input('organisation_id', $service->organisation_id),
                'slug' => $request->input('slug', $service->slug),
                'name' => $request->input('name', $service->name),
                'type' => $request->input('type', $service->type),
                'status' => $request->input('status', $service->status),
                'is_national' => $request->input('is_national', $service->is_national),
                'intro' => $request->input('intro', $service->intro),
                'description' => sanitize_markdown(
                    $request->input('description', $service->description)
                ),
                'wait_time' => $request->input('wait_time', $service->wait_time),
                'is_free' => $request->input('is_free', $service->is_free),
                'fees_text' => $request->input('fees_text', $service->fees_text),
                'fees_url' => $request->input('fees_url', $service->fees_url),
                'testimonial' => $request->input('testimonial', $service->testimonial),
                'video_embed' => $request->input('video_embed', $service->video_embed),
                'url' => $request->input('url', $service->url),
                'ios_app_url' => $request->input('ios_app_url', $service->ios_app_url),
                'android_app_url' => $request->input('android_app_url', $service->android_app_url),
                'contact_name' => $request->input('contact_name', $service->contact_name),
                'contact_phone' => $request->input('contact_phone', $service->contact_phone),
                'contact_email' => $request->input('contact_email', $service->contact_email),
                'show_referral_disclaimer' => $request->input('show_referral_disclaimer', $service->show_referral_disclaimer),
                'referral_method' => $request->input('referral_method', $service->referral_method),
                'referral_button_text' => $request->input('referral_button_text', $service->referral_button_text),
                'referral_email' => $request->input('referral_email', $service->referral_email),
                'referral_url' => $request->input('referral_url', $service->referral_url),
                'logo_file_id' => $request->input('logo_file_id', $service->logo_file_id),
                'score' => $request->input('score', $service->score),
                // This must always be updated regardless of the fields changed.
                'last_modified_at' => Date::now(),
            ]);

            $service->serviceCriterion->update([
                'age_group' => $request->input('criteria.age_group', $service->serviceCriterion->age_group),
                'disability' => $request->input('criteria.disability', $service->serviceCriterion->disability),
                'employment' => $request->input('criteria.employment', $service->serviceCriterion->employment),
                'gender' => $request->input('criteria.gender', $service->serviceCriterion->gender),
                'housing' => $request->input('criteria.housing', $service->serviceCriterion->housing),
                'income' => $request->input('criteria.income', $service->serviceCriterion->income),
                'language' => $request->input('criteria.language', $service->serviceCriterion->language),
                'other' => $request->input('criteria.other', $service->serviceCriterion->other),
            ]);

            // Update the useful info records.
            if ($request->has('useful_infos')) {
                $service->usefulInfos()->delete();
                $usefulInfos = $usefulInfoNormaliser->normaliseMultiple(
                    $request->input('useful_infos')
                );
                $service->usefulInfos()->createMany($usefulInfos);
            }

            // Update the offering records.
            if ($request->has('offerings')) {
                $service->offerings()->delete();
                $offerings = $offeringNormaliser->normaliseMultiple(
                    $request->input('offerings')
                );
                $service->offerings()->createMany($offerings);
            }

            // Update the gallery item records.
            if ($request->has('social_medias')) {
                $service->socialMedias()->delete();
                $socialMedias = $socialMediaNormaliser->normaliseMultiple(
                    $request->input('social_medias')
                );
                $service->socialMedias()->createMany($socialMedias);
            }

            // Update the social media records.
            if ($request->has('gallery_items')) {
                $service->serviceGalleryItems()->delete();
                $galleryItems = $galleryItemNormaliser->normaliseMultiple(
                    $request->input('gallery_items')
                );
                $service->serviceGalleryItems()->createMany($galleryItems);
            }

            // Ensure conditional fields are reset if needed.
            $service->resetConditionalFields();

            if ($request->filled('gallery_items')) {
                foreach ($request->gallery_items as $galleryItem) {
                    /** @var \App\Models\File $file */
                    $file = File::findOrFail($galleryItem['file_id'])->assigned();

                    // Create resized version for common dimensions.
                    foreach (config('hlp.cached_image_dimensions') as $maxDimension) {
                        $file->resizedVersion($maxDimension);
                    }
                }
            }

            if ($request->filled('logo_file_id')) {
                /** @var \App\Models\File $file */
                $file = File::findOrFail($request->logo_file_id)->assigned();

                // Create resized version for common dimensions.
                foreach (config('hlp.cached_image_dimensions') as $maxDimension) {
                    $file->resizedVersion($maxDimension);
                }
            }

            event(EndpointHit::onUpdate($request, "Updated service [{$service->id}]", $service));

            return new ServiceResource($service);
        });
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Http\Requests\Service\DestroyRequest $request
     * @param \App\Models\Service $service
     * @return \Illuminate\Http\Response
     */
    public function destroy(DestroyRequest $request, Service $service)
    {
        return DB::transaction(function () use ($request, $service) {
            event(EndpointHit::onDelete($request, "Deleted service [{$service->id}]", $service));

            $service->delete();

            return new ResourceDeleted('service');
        });
    }
}
