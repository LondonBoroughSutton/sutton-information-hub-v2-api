<?php

namespace App\Http\Controllers\Core\V1;

use App\Events\EndpointHit;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrganisationSignUpForm\StoreRequest;
use App\Http\Resources\OrganisationSignUpFormResource;
use App\Models\Organisation;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use App\Models\UserRole;
use App\Normalisers\OfferingNormaliser;
use App\Normalisers\SocialMediaNormaliser;
use App\Normalisers\UsefulInfoNormaliser;
use App\RoleManagement\RoleManagerInterface;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class OrganisationSignUpFormController extends Controller
{
    /**
     * OrganisationSignUpFormController constructor.
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * @param \App\Http\Requests\OrganisationSignUpForm\StoreRequest $request
     * @param \App\Normalisers\UsefulInfoNormaliser $usefulInfoNormaliser
     * @param \App\Normalisers\OfferingNormaliser $offeringNormaliser
     * @param \App\Normalisers\SocialMediaNormaliser $socialMediaNormaliser
     * @return \Illuminate\Http\Response
     */
    public function store(
        StoreRequest $request,
        UsefulInfoNormaliser $usefulInfoNormaliser,
        OfferingNormaliser $offeringNormaliser,
        SocialMediaNormaliser $socialMediaNormaliser
    ) {
        return DB::transaction(function () use (
            $request,
            $usefulInfoNormaliser,
            $offeringNormaliser,
            $socialMediaNormaliser
        ) {
            /** @var \App\Models\Organisation $organisation */
            $organisation = Organisation::create([
                'slug' => $request->input('organisation.slug'),
                'name' => $request->input('organisation.name'),
                'description' => sanitize_markdown(
                    $request->input('organisation.description')
                ),
                'url' => $request->input('organisation.url'),
                'email' => $request->input('organisation.email'),
                'phone' => $request->input('organisation.phone'),
            ]);

            /** @var \App\Models\Service $service */
            $service = $organisation->services()->create([
                'slug' => $request->input('service.slug'),
                'name' => $request->input('service.name'),
                'type' => $request->input('service.type'),
                'status' => Service::STATUS_INACTIVE,
                'intro' => $request->input('service.intro'),
                'description' => sanitize_markdown(
                    $request->input('service.description')
                ),
                'wait_time' => $request->input('service.wait_time'),
                'is_free' => $request->input('service.is_free'),
                'fees_text' => $request->input('service.fees_text'),
                'fees_url' => $request->input('service.fees_url'),
                'testimonial' => $request->input('service.testimonial'),
                'video_embed' => $request->input('service.video_embed'),
                'url' => $request->input('service.url'),
                'contact_name' => $request->input('service.contact_name'),
                'contact_phone' => $request->input('service.contact_phone'),
                'contact_email' => $request->input('service.contact_email'),
                'show_referral_disclaimer' => false,
                'referral_method' => Service::REFERRAL_METHOD_NONE,
                'referral_button_text' => null,
                'referral_email' => null,
                'referral_url' => null,
                'logo_file_id' => null,
                'last_modified_at' => Date::now(),
            ]);

            // Create the useful info records.
            $usefulInfos = $usefulInfoNormaliser->normaliseMultiple(
                $request->input('service.useful_infos')
            );
            $service->usefulInfos()->createMany($usefulInfos);

            // Create the offering records.
            $offerings = $offeringNormaliser->normaliseMultiple(
                $request->input('service.offerings')
            );
            $service->offerings()->createMany($offerings);

            // Create the social media records.
            $socialMedias = $socialMediaNormaliser->normaliseMultiple(
                $request->input('service.social_medias')
            );
            $service->socialMedias()->createMany($socialMedias);

            /** @var \App\Models\User $user */
            $user = User::create([
                'first_name' => $request->input('user.first_name'),
                'last_name' => $request->input('user.last_name'),
                'email' => $request->input('user.email'),
                'phone' => $request->input('user.phone'),
                'password' => bcrypt($request->input('user.password')),
            ]);

            /** @var \App\RoleManagement\RoleManagerInterface $roleManager */
            $roleManager = app()->make(RoleManagerInterface::class, [
                'user' => $user,
            ]);

            // Update the user roles.
            $roleManager->updateRoles(array_merge($user->userRoles->all(), [
                new UserRole([
                    'role_id' => Role::organisationAdmin()->id,
                    'organisation_id' => $organisation->id,
                ]),
            ]));

            event(
                EndpointHit::onCreate(
                    $request,
                    "Submitted organisation sign up form with organisation [{$organisation->id}], support listing [{$service->id}], and user [{$user->id}]"
                )
            );

            $resource = new OrganisationSignUpFormResource([
                'user' => $user,
                'organisation' => $organisation,
                'service' => $service,
            ]);

            return $resource->response($request)
                ->setStatusCode(Response::HTTP_CREATED);
        });
    }
}
