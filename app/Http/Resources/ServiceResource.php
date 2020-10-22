<?php

namespace App\Http\Resources;

use Carbon\CarbonImmutable;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'organisation_id' => $this->organisation_id,
            'has_logo' => $this->hasLogo(),
            'slug' => $this->slug,
            'name' => $this->name,
            'type' => $this->type,
            'status' => $this->status,
            'is_national' => $this->is_national,
            'intro' => $this->intro,
            'description' => $this->description,
            'wait_time' => $this->wait_time,
            'is_free' => $this->is_free,
            'fees_text' => $this->fees_text,
            'fees_url' => $this->fees_url,
            'testimonial' => $this->testimonial,
            'video_embed' => $this->video_embed,
            'url' => $this->url,
            'ios_app_url' => $this->ios_app_url,
            'android_app_url' => $this->android_app_url,
            'contact_name' => $this->contact_name,
            'contact_phone' => $this->contact_phone,
            'contact_email' => $this->contact_email,
            'show_referral_disclaimer' => $this->show_referral_disclaimer,
            'referral_method' => $this->referral_method,
            'referral_button_text' => $this->referral_button_text,
            'referral_email' => $this->referral_email,
            'referral_url' => $this->referral_url,
            'criteria' => new ServiceCriterionResource($this->serviceCriterion),
            'useful_infos' => UsefulInfoResource::collection($this->usefulInfos),
            'offerings' => OfferingResource::collection($this->offerings),
            'social_medias' => SocialMediaResource::collection($this->socialMedias),
            'score' => $this->score,
            'gallery_items' => ServiceGalleryItemResource::collection($this->serviceGalleryItems),
            'category_taxonomies' => TaxonomyResource::collection($this->taxonomies),
            'last_modified_at' => $this->last_modified_at->format(CarbonImmutable::ISO8601),
            'created_at' => $this->created_at->format(CarbonImmutable::ISO8601),
            'updated_at' => $this->updated_at->format(CarbonImmutable::ISO8601),

            // Relationships.
            'service_locations' => ServiceLocationResource::collection($this->whenLoaded('serviceLocations')),
            'organisation' => new OrganisationResource($this->whenLoaded('organisation')),
        ];
    }
}
