<?php

namespace Tests\Feature;

use App\Events\EndpointHit;
use App\Models\Audit;
use App\Models\File;
use App\Models\HolidayOpeningHour;
use App\Models\Location;
use App\Models\Organisation;
use App\Models\RegularOpeningHour;
use App\Models\Service;
use App\Models\ServiceLocation;
use App\Models\ServiceRefreshToken;
use App\Models\ServiceTaxonomy;
use App\Models\SocialMedia;
use App\Models\Taxonomy;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ServicesTest extends TestCase
{

    /**
     * Base payload to create a Service
     * @param Organisation $organisation
     *
     * @return Array
     **/
    public function createServicePayload(Organisation $organisation)
    {
        return [
            'organisation_id' => $organisation->id,
            'slug' => 'test-service',
            'name' => 'Test Service',
            'type' => Service::TYPE_SERVICE,
            'status' => Service::STATUS_INACTIVE,
            'is_national' => false,
            'intro' => 'This is a test intro',
            'description' => 'Lorem ipsum',
            'wait_time' => null,
            'is_free' => true,
            'fees_text' => null,
            'fees_url' => null,
            'testimonial' => null,
            'video_embed' => null,
            'url' => $this->faker->url,
            'ios_app_url' => null,
            'android_app_url' => null,
            'contact_name' => $this->faker->name,
            'contact_phone' => random_uk_phone(),
            'contact_email' => $this->faker->safeEmail,
            'show_referral_disclaimer' => false,
            'referral_method' => Service::REFERRAL_METHOD_NONE,
            'referral_button_text' => null,
            'referral_email' => null,
            'referral_url' => null,
            'criteria' => [
                'age_group' => '18+',
                'disability' => null,
                'employment' => null,
                'gender' => null,
                'housing' => null,
                'income' => null,
                'language' => null,
                'other' => null,
            ],
            'useful_infos' => [
                [
                    'title' => 'Did you know?',
                    'description' => 'Lorem ipsum',
                    'order' => 1,
                ],
            ],
            'offerings' => [
                [
                    'offering' => 'Weekly club',
                    'order' => 1,
                ],
            ],
            'social_medias' => [
                [
                    'type' => SocialMedia::TYPE_INSTAGRAM,
                    'url' => 'https://www.instagram.com/ayupdigital',
                ],
            ],
            'gallery_items' => [],
            'score' => null,
            'category_taxonomies' => [],
        ];
    }

    /**
     * Base payload to update a Service
     *
     * @param Service $service
     * @return Array
     **/
    public function updateServicePayload(Service $service)
    {
        return [
            'slug' => $service->slug,
            'name' => $service->name,
            'status' => $service->status,
            'is_national' => $service->is_national,
            'intro' => $service->intro,
            'description' => $service->description,
            'wait_time' => $service->wait_time,
            'is_free' => $service->is_free,
            'fees_text' => $service->fees_text,
            'fees_url' => $service->fees_url,
            'testimonial' => $service->testimonial,
            'video_embed' => $service->video_embed,
            'url' => $service->url,
            'ios_app_url' => $service->ios_app_url,
            'android_app_url' => $service->android_app_url,
            'contact_name' => $service->contact_name,
            'contact_phone' => $service->contact_phone,
            'contact_email' => $service->contact_email,
            'show_referral_disclaimer' => $service->show_referral_disclaimer,
            'referral_method' => $service->referral_method,
            'referral_button_text' => $service->referral_button_text,
            'referral_email' => $service->referral_email,
            'referral_url' => $service->referral_url,
            'criteria' => [
                'age_group' => $service->serviceCriterion->age_group,
                'disability' => $service->serviceCriterion->disability,
                'employment' => $service->serviceCriterion->employment,
                'gender' => $service->serviceCriterion->gender,
                'housing' => $service->serviceCriterion->housing,
                'income' => $service->serviceCriterion->income,
                'language' => $service->serviceCriterion->language,
                'other' => $service->serviceCriterion->other,
            ],
            'useful_infos' => [],
            'social_medias' => [],
            'score' => $service->score,
            'category_taxonomies' => [Taxonomy::category()->children()->firstOrFail()->id],
            'logo_file_id' => null,
        ];
    }

    /*
     * List all the services.
     */

    public function test_guest_can_list_them()
    {
        /** @var \App\Models\Service $service */
        $service = factory(Service::class)->create();
        $service->usefulInfos()->create([
            'title' => 'Did You Know?',
            'description' => 'This is a test description',
            'order' => 1,
        ]);
        $service->offerings()->create([
            'offering' => 'Weekly club',
            'order' => 1,
        ]);
        $service->socialMedias()->create([
            'type' => SocialMedia::TYPE_INSTAGRAM,
            'url' => 'https://www.instagram.com/ayupdigital/',
        ]);
        $service->serviceTaxonomies()->create([
            'taxonomy_id' => Taxonomy::category()->children()->first()->id,
        ]);

        $response = $this->json('GET', '/core/v1/services');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $service->id,
            'organisation_id' => $service->organisation_id,
            'has_logo' => $service->hasLogo(),
            'slug' => $service->slug,
            'name' => $service->name,
            'type' => $service->type,
            'status' => $service->status,
            'is_national' => $service->is_national,
            'intro' => $service->intro,
            'description' => $service->description,
            'wait_time' => $service->wait_time,
            'is_free' => $service->is_free,
            'fees_text' => $service->fees_text,
            'fees_url' => $service->fees_url,
            'testimonial' => $service->testimonial,
            'video_embed' => $service->video_embed,
            'url' => $service->url,
            'contact_name' => $service->contact_name,
            'contact_phone' => $service->contact_phone,
            'contact_email' => $service->contact_email,
            'show_referral_disclaimer' => $service->show_referral_disclaimer,
            'referral_method' => $service->referral_method,
            'referral_button_text' => $service->referral_button_text,
            'referral_email' => $service->referral_email,
            'referral_url' => $service->referral_url,
            'criteria' => [
                'age_group' => $service->serviceCriterion->age_group,
                'disability' => $service->serviceCriterion->disability,
                'employment' => $service->serviceCriterion->employment,
                'gender' => $service->serviceCriterion->gender,
                'housing' => $service->serviceCriterion->housing,
                'income' => $service->serviceCriterion->income,
                'language' => $service->serviceCriterion->language,
                'other' => $service->serviceCriterion->other,
            ],
            'useful_infos' => [
                [
                    'title' => 'Did You Know?',
                    'description' => 'This is a test description',
                    'order' => 1,
                ],
            ],
            'offerings' => [
                [
                    'offering' => 'Weekly club',
                    'order' => 1,
                ],
            ],
            'social_medias' => [
                [
                    'type' => SocialMedia::TYPE_INSTAGRAM,
                    'url' => 'https://www.instagram.com/ayupdigital/',
                ],
            ],
            'score' => $service->score,
            'gallery_items' => [],
            'category_taxonomies' => [
                [
                    'id' => Taxonomy::category()->children()->first()->id,
                    'parent_id' => Taxonomy::category()->children()->first()->parent_id,
                    'slug' => Taxonomy::category()->children()->first()->slug,
                    'name' => Taxonomy::category()->children()->first()->name,
                    'created_at' => Taxonomy::category()->children()->first()->created_at->format(CarbonImmutable::ISO8601),
                    'updated_at' => Taxonomy::category()->children()->first()->updated_at->format(CarbonImmutable::ISO8601),
                ],
            ],
            'last_modified_at' => $service->last_modified_at->format(CarbonImmutable::ISO8601),
            'created_at' => $service->created_at->format(CarbonImmutable::ISO8601),
        ]);
    }

    public function test_guest_can_filter_by_organisation_id()
    {
        $anotherService = factory(Service::class)->create();
        $service = factory(Service::class)->create();
        $service->usefulInfos()->create([
            'title' => 'Did You Know?',
            'description' => 'This is a test description',
            'order' => 1,
        ]);
        $service->offerings()->create([
            'offering' => 'Weekly club',
            'order' => 1,
        ]);
        $service->socialMedias()->create([
            'type' => SocialMedia::TYPE_INSTAGRAM,
            'url' => 'https://www.instagram.com/ayupdigital/',
        ]);
        $service->serviceTaxonomies()->create([
            'taxonomy_id' => Taxonomy::category()->children()->first()->id,
        ]);

        $response = $this->json('GET', "/core/v1/services?filter[organisation_id]={$service->organisation_id}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $service->id]);
        $response->assertJsonMissing(['id' => $anotherService->id]);
    }

    public function test_guest_can_filter_by_organisation_name()
    {
        $anotherService = factory(Service::class)->create([
            'organisation_id' => factory(Organisation::class)->create(['name' => 'Amazing Place']),
        ]);
        $service = factory(Service::class)->create([
            'organisation_id' => factory(Organisation::class)->create(['name' => 'Interesting House']),
        ]);
        $service->usefulInfos()->create([
            'title' => 'Did You Know?',
            'description' => 'This is a test description',
            'order' => 1,
        ]);
        $service->offerings()->create([
            'offering' => 'Weekly club',
            'order' => 1,
        ]);
        $service->socialMedias()->create([
            'type' => SocialMedia::TYPE_INSTAGRAM,
            'url' => 'https://www.instagram.com/ayupdigital/',
        ]);
        $service->serviceTaxonomies()->create([
            'taxonomy_id' => Taxonomy::category()->children()->first()->id,
        ]);

        $response = $this->json('GET', "/core/v1/services?filter[organisation_name]={$service->organisation->name}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $service->id]);
        $response->assertJsonMissing(['id' => $anotherService->id]);
    }

    public function test_guest_can_filter_by_has_category_taxonomies()
    {
        $service = factory(Service::class)->create();
        $service->serviceTaxonomies()->create([
            'taxonomy_id' => Taxonomy::category()->children()->first()->id,
        ]);

        factory(Service::class)->create();

        $response = $this->json('GET', '/core/v1/services?filter[has_category_taxonomies]=true');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['id' => $service->id]);
    }

    public function test_audit_created_when_listed()
    {
        $this->fakeEvents();

        $this->json('GET', '/core/v1/services');

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) {
            return ($event->getAction() === Audit::ACTION_READ);
        });
    }

    public function test_guest_can_sort_by_service_name()
    {
        $serviceOne = factory(Service::class)->create(['name' => 'Service A']);
        $serviceTwo = factory(Service::class)->create(['name' => 'Service B']);

        $response = $this->json('GET', '/core/v1/services?sort=-name');
        $data = $this->getResponseContent($response);

        $this->assertEquals($serviceOne->id, $data['data'][1]['id']);
        $this->assertEquals($serviceTwo->id, $data['data'][0]['id']);
    }

    public function test_guest_can_sort_by_organisation_name()
    {
        $serviceOne = factory(Service::class)->create([
            'organisation_id' => factory(Organisation::class)
                ->create(['name' => 'Organisation A'])
                ->id,
        ]);
        $serviceTwo = factory(Service::class)->create([
            'organisation_id' => factory(Organisation::class)
                ->create(['name' => 'Organisation B'])
                ->id,
        ]);

        $response = $this->json('GET', '/core/v1/services?sort=-organisation_name');
        $data = $this->getResponseContent($response);

        $this->assertEquals($serviceOne->organisation_id, $data['data'][1]['organisation_id']);
        $this->assertEquals($serviceTwo->organisation_id, $data['data'][0]['organisation_id']);
    }

    public function test_guest_can_sort_by_score()
    {
        $service1 = factory(Service::class)->create(['score' => 0]);
        $service2 = factory(Service::class)->create(['score' => 5]);
        $service3 = factory(Service::class)->create(['score' => 3]);
        $service4 = factory(Service::class)->create(['score' => 1]);
        $service5 = factory(Service::class)->create(['score' => 4]);
        $service6 = factory(Service::class)->create(['score' => 2]);

        $response = $this->json('GET', '/core/v1/services?sort=-score');
        $response->assertStatus(Response::HTTP_OK);
        $data = $this->getResponseContent($response);

        $this->assertEquals($service2->id, $data['data'][0]['id']);
        $this->assertEquals($service5->id, $data['data'][1]['id']);
        $this->assertEquals($service3->id, $data['data'][2]['id']);
        $this->assertEquals($service6->id, $data['data'][3]['id']);
        $this->assertEquals($service4->id, $data['data'][4]['id']);
        $this->assertEquals($service1->id, $data['data'][5]['id']);
    }

    /*
     * Create a service.
     */

    public function test_guest_cannot_create_one()
    {
        $response = $this->json('POST', '/core/v1/services');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_create_one()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeServiceWorker(factory(User::class)->create(), $service);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/services');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_create_one()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeServiceAdmin(factory(User::class)->create(), $service);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/services');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_can_create_an_inactive_one()
    {
        $organisation = factory(Organisation::class)->create();
        $user = $this->makeOrganisationAdmin(factory(User::class)->create(), $organisation);

        Passport::actingAs($user);

        $payload = $this->createServicePayload($organisation);
        $response = $this->json('POST', '/core/v1/services', $payload);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonFragment($payload);
    }

    public function test_organisation_admin_can_create_one_with_single_form_of_contact()
    {
        $organisation = factory(Organisation::class)->create();
        $user = $this->makeOrganisationAdmin(factory(User::class)->create(), $organisation);

        Passport::actingAs($user);

        $payload = $this->createServicePayload($organisation);
        $payload['contact_phone'] = null;
        $response = $this->json('POST', '/core/v1/services', $payload);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonFragment($payload);
    }

    public function test_organisation_admin_cannot_create_an_active_one()
    {
        $organisation = factory(Organisation::class)->create();

        $user = $this->makeOrganisationAdmin(factory(User::class)->create(), $organisation);

        Passport::actingAs($user);

        $payload = $this->createServicePayload($organisation);
        $payload['status'] = Service::STATUS_ACTIVE;
        $response = $this->json('POST', '/core/v1/services', $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_taxonomy_hierarchy_works_when_creating()
    {
        $taxonomy = Taxonomy::category()->children()->firstOrFail()->children()->firstOrFail();

        $organisation = factory(Organisation::class)->create();
        $user = $this->makeGlobalAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $payload = $this->createServicePayload($organisation);
        $payload['category_taxonomies'] = [$taxonomy->id];
        $response = $this->json('POST', '/core/v1/services', $payload);

        $response->assertStatus(Response::HTTP_CREATED);
        $service = Service::findOrFail(json_decode($response->getContent(), true)['data']['id']);
        $this->assertDatabaseHas(table(ServiceTaxonomy::class), [
            'service_id' => $service->id,
            'taxonomy_id' => $taxonomy->id,
        ]);
        $this->assertDatabaseHas(table(ServiceTaxonomy::class), [
            'service_id' => $service->id,
            'taxonomy_id' => $taxonomy->parent_id,
        ]);
    }

    public function test_organisation_admin_for_another_organisation_cannot_create_one()
    {
        $anotherOrganisation = factory(Organisation::class)->create();
        $organisation = factory(Organisation::class)->create();
        $user = $this->makeOrganisationAdmin(factory(User::class)->create(), $organisation);

        Passport::actingAs($user);

        $payload = $this->createServicePayload($anotherOrganisation);
        $payload['category_taxonomies'] = [Taxonomy::category()->children()->firstOrFail()->id];
        $response = $this->json('POST', '/core/v1/services', $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_audit_created_when_created()
    {
        $this->fakeEvents();

        $organisation = factory(Organisation::class)->create();
        $user = $this->makeGlobalAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $payload = $this->createServicePayload($organisation);
        $payload['category_taxonomies'] = [Taxonomy::category()->children()->firstOrFail()->id];
        $response = $this->json('POST', '/core/v1/services', $payload);

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $response) {
            return ($event->getAction() === Audit::ACTION_CREATE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $this->getResponseContent($response)['data']['id']);
        });
    }

    public function test_global_admin_can_create_an_active_one_with_taxonomies()
    {
        $organisation = factory(Organisation::class)->create();
        $user = $this->makeGlobalAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $payload = $this->createServicePayload($organisation);
        $payload['status'] = Service::STATUS_ACTIVE;
        $payload['category_taxonomies'] = [Taxonomy::category()->children()->firstOrFail()->id];

        $response = $this->json('POST', '/core/v1/services', $payload);

        $response->assertStatus(Response::HTTP_CREATED);
        $responsePayload = $payload;
        $responsePayload['category_taxonomies'] = [
            [
                'id' => Taxonomy::category()->children()->firstOrFail()->id,
                'parent_id' => Taxonomy::category()->children()->firstOrFail()->parent_id,
                'slug' => Taxonomy::category()->children()->firstOrFail()->slug,
                'name' => Taxonomy::category()->children()->firstOrFail()->name,
                'created_at' => Taxonomy::category()->children()->firstOrFail()->created_at->format(CarbonImmutable::ISO8601),
                'updated_at' => Taxonomy::category()->children()->firstOrFail()->updated_at->format(CarbonImmutable::ISO8601),
            ],
        ];
        $response->assertJsonFragment($responsePayload);
    }

    public function test_global_admin_can_create_one_accepting_referrals()
    {
        $organisation = factory(Organisation::class)->create();
        $user = $this->makeGlobalAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $payload = $this->createServicePayload($organisation);
        $payload['status'] = Service::STATUS_ACTIVE;
        $payload['show_referral_disclaimer'] = true;
        $payload['referral_method'] = Service::REFERRAL_METHOD_INTERNAL;
        $payload['referral_email'] = $this->faker->safeEmail;
        $payload['category_taxonomies'] = [Taxonomy::category()->children()->firstOrFail()->id];

        $response = $this->json('POST', '/core/v1/services', $payload);

        $response->assertStatus(Response::HTTP_CREATED);
        $responsePayload = $payload;
        $responsePayload['category_taxonomies'] = [
            [
                'id' => Taxonomy::category()->children()->firstOrFail()->id,
                'parent_id' => Taxonomy::category()->children()->firstOrFail()->parent_id,
                'slug' => Taxonomy::category()->children()->firstOrFail()->slug,
                'name' => Taxonomy::category()->children()->firstOrFail()->name,
                'created_at' => Taxonomy::category()->children()->firstOrFail()->created_at->format(CarbonImmutable::ISO8601),
                'updated_at' => Taxonomy::category()->children()->firstOrFail()->updated_at->format(CarbonImmutable::ISO8601),
            ],
        ];
        $response->assertJsonFragment($responsePayload);
    }

    public function test_global_admin_cannot_create_one_with_referral_disclaimer_showing()
    {
        $organisation = factory(Organisation::class)->create();
        $user = $this->makeGlobalAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $payload = $this->createServicePayload($organisation);
        $payload['show_referral_disclaimer'] = true;

        $response = $this->json('POST', '/core/v1/services', $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_super_admin_can_create_one_with_referral_disclaimer_showing()
    {
        $organisation = factory(Organisation::class)->create();
        $user = $this->makeSuperAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $taxonomy = Taxonomy::category()
            ->children()
            ->firstOrFail();

        $payload = $this->createServicePayload($organisation);
        $payload['show_referral_disclaimer'] = true;
        $payload['category_taxonomies'] = [$taxonomy->id];

        $response = $this->json('POST', '/core/v1/services', $payload);

        $response->assertStatus(Response::HTTP_CREATED);
    }

    public function test_super_admin_can_create_one_with_a_score_between_1_and_5()
    {
        $organisation = factory(Organisation::class)->create();
        $superAdmin = factory(User::class)->create()->makeSuperAdmin();
        $globalAdmin = factory(User::class)->create()->makeGlobalAdmin();
        $orgAdmin = factory(User::class)->create()->makeOrganisationAdmin($organisation);

        $taxonomy = Taxonomy::category()
            ->children()
            ->firstOrFail();

        $payload = $this->createServicePayload($organisation);
        $payload['category_taxonomies'] = [$taxonomy->id];
        $payload['score'] = 5;

        Passport::actingAs($orgAdmin);

        $response = $this->json('POST', '/core/v1/services', $payload);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        Passport::actingAs($globalAdmin);

        $response = $this->json('POST', '/core/v1/services', $payload);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $payload['score'] = 0;
        Passport::actingAs($superAdmin);

        $response = $this->json('POST', '/core/v1/services', $payload);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $payload['score'] = 6;
        Passport::actingAs($superAdmin);

        $response = $this->json('POST', '/core/v1/services', $payload);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $payload['score'] = -1;
        Passport::actingAs($superAdmin);

        $response = $this->json('POST', '/core/v1/services', $payload);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $payload['score'] = 5;
        Passport::actingAs($superAdmin);

        $response = $this->json('POST', '/core/v1/services', $payload);
        $response->assertStatus(Response::HTTP_CREATED);
    }

    /*
     * Get a specific service.
     */

    public function test_guest_can_view_one()
    {
        $service = factory(Service::class)->create();
        $service->usefulInfos()->create([
            'title' => 'Did You Know?',
            'description' => 'This is a test description',
            'order' => 1,
        ]);
        $service->offerings()->create([
            'offering' => 'Weekly club',
            'order' => 1,
        ]);
        $service->socialMedias()->create([
            'type' => SocialMedia::TYPE_INSTAGRAM,
            'url' => 'https://www.instagram.com/ayupdigital/',
        ]);
        $service->serviceTaxonomies()->create([
            'taxonomy_id' => Taxonomy::category()->children()->first()->id,
        ]);

        $response = $this->json('GET', "/core/v1/services/{$service->id}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $service->id,
            'organisation_id' => $service->organisation_id,
            'has_logo' => $service->hasLogo(),
            'slug' => $service->slug,
            'name' => $service->name,
            'type' => $service->type,
            'status' => $service->status,
            'is_national' => $service->is_national,
            'intro' => $service->intro,
            'description' => $service->description,
            'wait_time' => $service->wait_time,
            'is_free' => $service->is_free,
            'fees_text' => $service->fees_text,
            'fees_url' => $service->fees_url,
            'testimonial' => $service->testimonial,
            'video_embed' => $service->video_embed,
            'url' => $service->url,
            'ios_app_url' => $service->ios_app_url,
            'android_app_url' => $service->android_app_url,
            'contact_name' => $service->contact_name,
            'contact_phone' => $service->contact_phone,
            'contact_email' => $service->contact_email,
            'show_referral_disclaimer' => $service->show_referral_disclaimer,
            'referral_method' => $service->referral_method,
            'referral_button_text' => $service->referral_button_text,
            'referral_email' => $service->referral_email,
            'referral_url' => $service->referral_url,
            'criteria' => [
                'age_group' => $service->serviceCriterion->age_group,
                'disability' => $service->serviceCriterion->disability,
                'employment' => $service->serviceCriterion->employment,
                'gender' => $service->serviceCriterion->gender,
                'housing' => $service->serviceCriterion->housing,
                'income' => $service->serviceCriterion->income,
                'language' => $service->serviceCriterion->language,
                'other' => $service->serviceCriterion->other,
            ],
            'useful_infos' => [
                [
                    'title' => 'Did You Know?',
                    'description' => 'This is a test description',
                    'order' => 1,
                ],
            ],
            'offerings' => [
                [
                    'offering' => 'Weekly club',
                    'order' => 1,
                ],
            ],
            'social_medias' => [
                [
                    'type' => SocialMedia::TYPE_INSTAGRAM,
                    'url' => 'https://www.instagram.com/ayupdigital/',
                ],
            ],
            'score' => $service->score,
            'category_taxonomies' => [
                [
                    'id' => Taxonomy::category()->children()->first()->id,
                    'parent_id' => Taxonomy::category()->children()->first()->parent_id,
                    'slug' => Taxonomy::category()->children()->first()->slug,
                    'name' => Taxonomy::category()->children()->first()->name,
                    'created_at' => Taxonomy::category()->children()->first()->created_at->format(CarbonImmutable::ISO8601),
                    'updated_at' => Taxonomy::category()->children()->first()->updated_at->format(CarbonImmutable::ISO8601),
                ],
            ],
            'gallery_items' => [],
            'last_modified_at' => $service->last_modified_at->format(CarbonImmutable::ISO8601),
            'created_at' => $service->created_at->format(CarbonImmutable::ISO8601),
        ]);
    }

    public function test_guest_can_view_one_by_slug()
    {
        $service = factory(Service::class)->create();
        $service->usefulInfos()->create([
            'title' => 'Did You Know?',
            'description' => 'This is a test description',
            'order' => 1,
        ]);
        $service->offerings()->create([
            'offering' => 'Weekly club',
            'order' => 1,
        ]);
        $service->socialMedias()->create([
            'type' => SocialMedia::TYPE_INSTAGRAM,
            'url' => 'https://www.instagram.com/ayupdigital/',
        ]);
        $service->serviceTaxonomies()->create([
            'taxonomy_id' => Taxonomy::category()->children()->first()->id,
        ]);

        $response = $this->json('GET', "/core/v1/services/{$service->slug}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $service->id,
            'organisation_id' => $service->organisation_id,
            'has_logo' => $service->hasLogo(),
            'slug' => $service->slug,
            'name' => $service->name,
            'type' => $service->type,
            'status' => $service->status,
            'is_national' => $service->is_national,
            'intro' => $service->intro,
            'description' => $service->description,
            'wait_time' => $service->wait_time,
            'is_free' => $service->is_free,
            'fees_text' => $service->fees_text,
            'fees_url' => $service->fees_url,
            'testimonial' => $service->testimonial,
            'video_embed' => $service->video_embed,
            'url' => $service->url,
            'ios_app_url' => $service->ios_app_url,
            'android_app_url' => $service->android_app_url,
            'contact_name' => $service->contact_name,
            'contact_phone' => $service->contact_phone,
            'contact_email' => $service->contact_email,
            'show_referral_disclaimer' => $service->show_referral_disclaimer,
            'referral_method' => $service->referral_method,
            'referral_button_text' => $service->referral_button_text,
            'referral_email' => $service->referral_email,
            'referral_url' => $service->referral_url,
            'criteria' => [
                'age_group' => $service->serviceCriterion->age_group,
                'disability' => $service->serviceCriterion->disability,
                'employment' => $service->serviceCriterion->employment,
                'gender' => $service->serviceCriterion->gender,
                'housing' => $service->serviceCriterion->housing,
                'income' => $service->serviceCriterion->income,
                'language' => $service->serviceCriterion->language,
                'other' => $service->serviceCriterion->other,
            ],
            'useful_infos' => [
                [
                    'title' => 'Did You Know?',
                    'description' => 'This is a test description',
                    'order' => 1,
                ],
            ],
            'offerings' => [
                [
                    'offering' => 'Weekly club',
                    'order' => 1,
                ],
            ],
            'social_medias' => [
                [
                    'type' => SocialMedia::TYPE_INSTAGRAM,
                    'url' => 'https://www.instagram.com/ayupdigital/',
                ],
            ],
            'score' => $service->score,
            'category_taxonomies' => [
                [
                    'id' => Taxonomy::category()->children()->first()->id,
                    'parent_id' => Taxonomy::category()->children()->first()->parent_id,
                    'slug' => Taxonomy::category()->children()->first()->slug,
                    'name' => Taxonomy::category()->children()->first()->name,
                    'created_at' => Taxonomy::category()->children()->first()->created_at->format(CarbonImmutable::ISO8601),
                    'updated_at' => Taxonomy::category()->children()->first()->updated_at->format(CarbonImmutable::ISO8601),
                ],
            ],
            'gallery_items' => [],
            'last_modified_at' => $service->last_modified_at->format(CarbonImmutable::ISO8601),
            'created_at' => $service->created_at->format(CarbonImmutable::ISO8601),
        ]);
    }

    public function test_audit_created_when_viewed()
    {
        $this->fakeEvents();

        $service = factory(Service::class)->create();
        $service->usefulInfos()->create([
            'title' => 'Did You Know?',
            'description' => 'This is a test description',
            'order' => 1,
        ]);
        $service->offerings()->create([
            'offering' => 'Weekly club',
            'order' => 1,
        ]);
        $service->socialMedias()->create([
            'type' => SocialMedia::TYPE_INSTAGRAM,
            'url' => 'https://www.instagram.com/ayupdigital/',
        ]);
        $service->serviceTaxonomies()->create([
            'taxonomy_id' => Taxonomy::category()->children()->first()->id,
        ]);

        $this->json('GET', "/core/v1/services/{$service->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($service) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getModel()->id === $service->id);
        });
    }

    /*
     * Update a specific service.
     */

    public function test_guest_cannot_update_one()
    {
        $service = factory(Service::class)->create();

        $response = $this->json('PUT', "/core/v1/services/{$service->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_update_one()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeServiceWorker(factory(User::class)->create(), $service);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/services/{$service->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_can_update_one()
    {
        $service = factory(Service::class)->create([
            'slug' => 'test-service',
            'status' => Service::STATUS_ACTIVE,
        ]);
        $taxonomy = Taxonomy::category()->children()->firstOrFail();
        $service->syncServiceTaxonomies(new Collection([$taxonomy]));
        $user = $this->makeServiceAdmin(factory(User::class)->create(), $service);

        Passport::actingAs($user);

        $payload = $this->updateServicePayload($service);
        $payload['intro'] = 'New intro';

        $response = $this->json('PUT', "/core/v1/services/{$service->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(array_merge(
            ['intro' => 'New intro'],
            [
                'category_taxonomies' => [
                    [
                        'id' => $taxonomy->id,
                        'parent_id' => $taxonomy->parent_id,
                        'slug' => $taxonomy->slug,
                        'name' => $taxonomy->name,
                        'created_at' => $taxonomy->created_at->format(CarbonImmutable::ISO8601),
                        'updated_at' => $taxonomy->updated_at->format(CarbonImmutable::ISO8601),
                    ],
                ],
            ]
        ));
    }

    public function test_service_admin_can_update_one_with_single_form_of_contact()
    {
        $service = factory(Service::class)->create([
            'slug' => 'test-service',
            'status' => Service::STATUS_ACTIVE,
        ]);
        $taxonomy = Taxonomy::category()->children()->firstOrFail();
        $service->syncServiceTaxonomies(new Collection([$taxonomy]));
        $user = $this->makeServiceAdmin(factory(User::class)->create(), $service);

        Passport::actingAs($user);

        $payload = $this->updateServicePayload($service);
        $payload['contact_phone'] = null;

        $response = $this->json('PUT', "/core/v1/services/{$service->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(array_merge(
            ['contact_phone' => null],
            [
                'category_taxonomies' => [
                    [
                        'id' => $taxonomy->id,
                        'parent_id' => $taxonomy->parent_id,
                        'slug' => $taxonomy->slug,
                        'name' => $taxonomy->name,
                        'created_at' => $taxonomy->created_at->format(CarbonImmutable::ISO8601),
                        'updated_at' => $taxonomy->updated_at->format(CarbonImmutable::ISO8601),
                    ],
                ],
            ]
        ));
    }

    public function test_global_admin_can_update_most_fields_for_one()
    {
        $service = factory(Service::class)->create([
            'slug' => 'test-service',
            'status' => Service::STATUS_ACTIVE,
        ]);
        $taxonomy = Taxonomy::category()->children()->firstOrFail();
        $service->syncServiceTaxonomies(new Collection([$taxonomy]));
        $user = $this->makeGlobalAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $payload = [
            'slug' => 'test-service',
            'name' => 'Test Service',
            'type' => Service::TYPE_SERVICE,
            'status' => Service::STATUS_ACTIVE,
            'is_national' => false,
            'intro' => 'This is a test intro',
            'description' => 'Lorem ipsum',
            'wait_time' => null,
            'is_free' => true,
            'fees_text' => null,
            'fees_url' => null,
            'testimonial' => null,
            'video_embed' => null,
            'url' => $this->faker->url,
            'contact_name' => $this->faker->name,
            'contact_phone' => random_uk_phone(),
            'contact_email' => $this->faker->safeEmail,
            'show_referral_disclaimer' => false,
            'referral_method' => $service->referral_method,
            'referral_button_text' => $service->referral_button_text,
            'referral_email' => $service->referral_email,
            'referral_url' => $service->referral_url,
            'criteria' => [
                'age_group' => '18+',
                'disability' => null,
                'employment' => null,
                'gender' => null,
                'housing' => null,
                'income' => null,
                'language' => null,
                'other' => null,
            ],
            'useful_infos' => [
                [
                    'title' => 'Did you know?',
                    'description' => 'Lorem ipsum',
                    'order' => 1,
                ],
            ],
            'offerings' => [
                [
                    'offering' => 'Weekly club',
                    'order' => 1,
                ],
            ],
            'social_medias' => [
                [
                    'type' => SocialMedia::TYPE_INSTAGRAM,
                    'url' => 'https://www.instagram.com/ayupdigital',
                ],
            ],
            'score' => $service->score,
            'gallery_items' => [],
            'category_taxonomies' => [
                $taxonomy->id,
            ],
        ];

        $response = $this->json('PUT', "/core/v1/services/{$service->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(array_merge(
            $payload,
            [
                'category_taxonomies' => [
                    [
                        'id' => $taxonomy->id,
                        'parent_id' => $taxonomy->parent_id,
                        'slug' => $taxonomy->slug,
                        'name' => $taxonomy->name,
                        'created_at' => $taxonomy->created_at->format(CarbonImmutable::ISO8601),
                        'updated_at' => $taxonomy->updated_at->format(CarbonImmutable::ISO8601),
                    ],
                ],
            ]
        ));
    }

    public function test_global_admin_cannot_update_show_referral_disclaimer_for_one()
    {
        $service = factory(Service::class)->create([
            'slug' => 'test-service',
            'status' => Service::STATUS_ACTIVE,
        ]);
        $taxonomy = Taxonomy::category()->children()->firstOrFail();
        $service->syncServiceTaxonomies(new Collection([$taxonomy]));
        $user = $this->makeGlobalAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $payload = $this->updateServicePayload($service);
        $payload['show_referral_disclaimer'] = true;

        $response = $this->json('PUT', "/core/v1/services/{$service->id}", $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_audit_created_when_updated()
    {
        $this->fakeEvents();

        $service = factory(Service::class)->create([
            'slug' => 'test-service',
            'status' => Service::STATUS_ACTIVE,
        ]);
        $taxonomy = Taxonomy::category()->children()->firstOrFail();
        $service->syncServiceTaxonomies(new Collection([$taxonomy]));
        $user = $this->makeServiceAdmin(factory(User::class)->create(), $service);

        Passport::actingAs($user);

        $payload = $this->updateServicePayload($service);
        $payload['intro'] = 'New intro';

        $this->json('PUT', "/core/v1/services/{$service->id}", $payload);

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $service) {
            return ($event->getAction() === Audit::ACTION_UPDATE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $service->id);
        });
    }

    public function test_service_admin_cannot_update_taxonomies()
    {
        $service = factory(Service::class)->create();
        $taxonomy = Taxonomy::category()->children()->firstOrFail();
        $service->syncServiceTaxonomies(new Collection([$taxonomy]));
        $user = $this->makeServiceAdmin(factory(User::class)->create(), $service);

        Passport::actingAs($user);

        $newTaxonomy = Taxonomy::category()
            ->children()
            ->where('id', '!=', $taxonomy->id)
            ->firstOrFail();

        $payload = $this->updateServicePayload($service);
        $payload['category_taxonomies'] = [
            $taxonomy->id,
            $newTaxonomy->id,
        ];

        $response = $this->json('PUT', "/core/v1/services/{$service->id}", $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_global_admin_can_update_taxonomies()
    {
        $service = factory(Service::class)->create();
        $taxonomy = Taxonomy::category()->children()->firstOrFail();
        $service->syncServiceTaxonomies(new Collection([$taxonomy]));
        $user = $this->makeGlobalAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $newTaxonomy = Taxonomy::category()
            ->children()
            ->where('id', '!=', $taxonomy->id)
            ->firstOrFail();

        $payload = $this->updateServicePayload($service);
        $payload['category_taxonomies'] = [
            $taxonomy->id,
            $newTaxonomy->id,
        ];

        $response = $this->json('PUT', "/core/v1/services/{$service->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);
    }

    public function test_service_admin_cannot_update_status()
    {
        $service = factory(Service::class)->create([
            'slug' => 'test-service',
            'status' => Service::STATUS_ACTIVE,
        ]);
        $taxonomy = Taxonomy::category()->children()->firstOrFail();
        $service->syncServiceTaxonomies(new Collection([$taxonomy]));
        $user = $this->makeServiceAdmin(factory(User::class)->create(), $service);

        Passport::actingAs($user);

        $payload = $this->updateServicePayload($service);
        $payload['status'] = Service::STATUS_INACTIVE;

        $response = $this->json('PUT', "/core/v1/services/{$service->id}", $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_service_admin_cannot_update_slug()
    {
        $service = factory(Service::class)->create([
            'slug' => 'test-service',
            'status' => Service::STATUS_ACTIVE,
        ]);
        $taxonomy = Taxonomy::category()->children()->firstOrFail();
        $service->syncServiceTaxonomies(new Collection([$taxonomy]));
        $user = $this->makeServiceAdmin(factory(User::class)->create(), $service);

        Passport::actingAs($user);

        $payload = $this->updateServicePayload($service);
        $payload['slug'] = 'new-slug';

        $response = $this->json('PUT', "/core/v1/services/{$service->id}", $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_global_admin_cannot_update_status()
    {
        $service = factory(Service::class)->create([
            'slug' => 'test-service',
            'status' => Service::STATUS_ACTIVE,
        ]);
        $taxonomy = Taxonomy::category()->children()->firstOrFail();
        $service->syncServiceTaxonomies(new Collection([$taxonomy]));
        $user = $this->makeGlobalAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $payload = $this->updateServicePayload($service);
        $payload['status'] = Service::STATUS_INACTIVE;

        $response = $this->json('PUT', "/core/v1/services/{$service->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);
    }

    public function test_global_admin_cannot_update_slug()
    {
        $service = factory(Service::class)->create([
            'slug' => 'test-service',
            'status' => Service::STATUS_ACTIVE,
        ]);
        $taxonomy = Taxonomy::category()->children()->firstOrFail();
        $service->syncServiceTaxonomies(new Collection([$taxonomy]));
        $user = $this->makeGlobalAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $payload = $this->updateServicePayload($service);
        $payload['slug'] = 'new-slug';

        $response = $this->json('PUT', "/core/v1/services/{$service->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);
    }

    public function test_global_admin_cannot_update_is_national_if_service_location_exists()
    {
        $service = factory(Service::class)->create([
            'slug' => 'test-service',
            'is_national' => false,
        ]);
        $taxonomy = Taxonomy::category()->children()->firstOrFail();
        $location = factory(Location::class)->create();
        $service = factory(Service::class)->create();
        $service->serviceLocations()->create(['location_id' => $location->id]);

        $user = $this->makeGlobalAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $payload = $this->updateServicePayload($service);
        $payload['is_national'] = true;

        $response = $this->json('PUT', "/core/v1/services/{$service->id}", $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_referral_email_must_be_provided_when_referral_type_is_internal()
    {
        $service = factory(Service::class)->create([
            'slug' => 'test-service',
            'status' => Service::STATUS_ACTIVE,
        ]);
        $taxonomy = Taxonomy::category()->children()->firstOrFail();
        $service->syncServiceTaxonomies(new Collection([$taxonomy]));
        $user = $this->makeGlobalAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $payload = $this->updateServicePayload($service);
        $payload['status'] = Service::STATUS_ACTIVE;
        $payload['show_referral_disclaimer'] = true;
        $payload['referral_method'] = Service::REFERRAL_METHOD_INTERNAL;
        $payload['referral_button_text'] = null;
        $payload['referral_email'] = null;
        $payload['referral_url'] = null;
        $payload['category_taxonomies'] = [
            $taxonomy->id,
        ];

        $response = $this->json('PUT', "/core/v1/services/{$service->id}", $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertArrayHasKey('referral_email', $this->getResponseContent($response)['errors']);
    }

    public function test_service_admin_cannot_update_referral_details()
    {
        $service = factory(Service::class)->create([
            'slug' => 'test-service',
            'status' => Service::STATUS_ACTIVE,
            'is_national' => false,
            'referral_method' => Service::REFERRAL_METHOD_INTERNAL,
        ]);
        $taxonomy = Taxonomy::category()->children()->firstOrFail();
        $service->syncServiceTaxonomies(new Collection([$taxonomy]));
        $user = $this->makeServiceAdmin(factory(User::class)->create(), $service);

        Passport::actingAs($user);

        $payload = $this->updateServicePayload($service);
        $payload['referral_method'] = Service::REFERRAL_METHOD_NONE;
        $payload['referral_button_text'] = null;
        $payload['referral_email'] = null;
        $payload['referral_url'] = null;

        $response = $this->json('PUT', "/core/v1/services/{$service->id}", $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertCount(1, $this->getResponseContent($response)['errors']);
        $this->assertArrayHasKey('referral_method', $this->getResponseContent($response)['errors']);
    }

    public function test_global_admin_can_update_referral_details()
    {
        $service = factory(Service::class)->create([
            'slug' => 'test-service',
            'status' => Service::STATUS_ACTIVE,
            'is_national' => false,
            'referral_method' => Service::REFERRAL_METHOD_INTERNAL,
            'referral_email' => $this->faker->safeEmail,
        ]);
        $taxonomy = Taxonomy::category()->children()->firstOrFail();
        $service->syncServiceTaxonomies(new Collection([$taxonomy]));
        $user = $this->makeGlobalAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $payload = $this->updateServicePayload($service);
        $payload['referral_method'] = Service::REFERRAL_METHOD_NONE;
        $payload['referral_button_text'] = null;
        $payload['referral_email'] = null;
        $payload['referral_url'] = null;

        $response = $this->json('PUT', "/core/v1/services/{$service->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);
    }

    public function test_service_admin_can_update_gallery_items()
    {
        $service = factory(Service::class)->create([
            'slug' => 'test-service',
            'status' => Service::STATUS_ACTIVE,
            'is_national' => false,
            'referral_method' => Service::REFERRAL_METHOD_INTERNAL,
            'referral_email' => $this->faker->safeEmail,
        ]);
        $taxonomy = Taxonomy::category()->children()->firstOrFail();
        $service->syncServiceTaxonomies(new Collection([$taxonomy]));
        $user = $this->makeGlobalAdmin(factory(User::class)->create());
        $image = Storage::disk('local')->get('/test-data/image.png');

        Passport::actingAs($user);

        $imageResponse = $this->json('POST', '/core/v1/files', [
            'is_private' => false,
            'mime_type' => 'image/png',
            'file' => 'data:image/png;base64,' . base64_encode($image),
        ]);

        $response = $this->json('PUT', "/core/v1/services/{$service->id}", [
            'gallery_items' => [
                [
                    'file_id' => $this->getResponseContent($imageResponse, 'data.id'),
                ],
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
    }

    public function test_only_partial_fields_can_be_updated()
    {
        $service = factory(Service::class)->create([
            'slug' => 'test-service',
            'status' => Service::STATUS_ACTIVE,
        ]);
        $taxonomy = Taxonomy::category()->children()->firstOrFail();
        $service->syncServiceTaxonomies(new Collection([$taxonomy]));
        $user = $this->makeGlobalAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/services/{$service->id}", [
            'slug' => 'random-slug',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'slug' => 'random-slug',
        ]);
    }

    public function test_referral_url_required_when_referral_method_not_updated_with_it()
    {
        $service = factory(Service::class)->create([
            'slug' => 'test-service',
            'status' => Service::STATUS_ACTIVE,
            'is_national' => false,
            'referral_method' => Service::REFERRAL_METHOD_EXTERNAL,
            'referral_url' => $this->faker->url,
        ]);
        $taxonomy = Taxonomy::category()->children()->firstOrFail();
        $service->syncServiceTaxonomies(new Collection([$taxonomy]));
        $user = $this->makeGlobalAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/services/{$service->id}", [
            'referral_url' => null,
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_organisation_admin_cannot_update_organisation_id()
    {
        $service = factory(Service::class)->create([
            'slug' => 'test-service',
            'status' => Service::STATUS_ACTIVE,
        ]);
        $taxonomy = Taxonomy::category()->children()->firstOrFail();
        $service->syncServiceTaxonomies(new Collection([$taxonomy]));
        $user = $this->makeOrganisationAdmin(factory(User::class)->create(), $service->organisation);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/services/{$service->id}", [
            'organisation_id' => factory(Organisation::class)->create()->id,
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_global_admin_can_update_organisation_id()
    {
        $service = factory(Service::class)->create([
            'slug' => 'test-service',
            'status' => Service::STATUS_ACTIVE,
        ]);
        $taxonomy = Taxonomy::category()->children()->firstOrFail();
        $service->syncServiceTaxonomies(new Collection([$taxonomy]));
        $user = $this->makeGlobalAdmin(factory(User::class)->create());
        $organisation = factory(Organisation::class)->create();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/services/{$service->id}", [
            'organisation_id' => $organisation->id,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'organisation_id' => $organisation->id,
        ]);
    }

    public function test_super_admin_can_update_score_between_1_and_5()
    {
        $service = factory(Service::class)->create([
            'score' => 3,
        ]);
        $superAdmin = factory(User::class)->create()->makeSuperAdmin();
        $globalAdmin = factory(User::class)->create()->makeGlobalAdmin();
        $orgAdmin = factory(User::class)->create()->makeOrganisationAdmin($service->organisation);

        $payload = $this->updateServicePayload($service);
        $payload['score'] = 5;

        Passport::actingAs($orgAdmin);

        $response = $this->json('PUT', "/core/v1/services/{$service->id}", $payload);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        Passport::actingAs($globalAdmin);

        $response = $this->json('PUT', "/core/v1/services/{$service->id}", $payload);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $payload['score'] = 0;
        Passport::actingAs($superAdmin);

        $response = $this->json('PUT', "/core/v1/services/{$service->id}", $payload);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $payload['score'] = 6;
        Passport::actingAs($superAdmin);

        $response = $this->json('PUT', "/core/v1/services/{$service->id}", $payload);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $payload['score'] = -1;
        Passport::actingAs($superAdmin);

        $response = $this->json('PUT', "/core/v1/services/{$service->id}", $payload);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $payload['score'] = 5;
        Passport::actingAs($superAdmin);

        $response = $this->json('PUT', "/core/v1/services/{$service->id}", $payload);
        $response->assertStatus(Response::HTTP_OK);
    }

    /*
     * Delete a specific service.
     */

    public function test_guest_cannot_delete_one()
    {
        $service = factory(Service::class)->create();

        $response = $this->json('DELETE', "/core/v1/services/{$service->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_delete_one()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeServiceWorker(factory(User::class)->create(), $service);

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/services/{$service->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_delete_one()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeServiceAdmin(factory(User::class)->create(), $service);

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/services/{$service->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_cannot_delete_one()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeOrganisationAdmin(factory(User::class)->create(), $service->organisation);

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/services/{$service->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_global_admin_can_delete_one()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeGlobalAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/services/{$service->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing((new Service())->getTable(), ['id' => $service->id]);
    }

    public function test_audit_created_when_deleted()
    {
        $this->fakeEvents();

        $service = factory(Service::class)->create();
        $user = $this->makeSuperAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $this->json('DELETE', "/core/v1/services/{$service->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $service) {
            return ($event->getAction() === Audit::ACTION_DELETE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $service->id);
        });
    }

    public function test_service_can_be_deleted_when_service_location_has_opening_hours()
    {
        $service = factory(Service::class)->create();
        $serviceLocation = factory(ServiceLocation::class)->create([
            'service_id' => $service->id,
        ]);
        factory(RegularOpeningHour::class)->create([
            'service_location_id' => $serviceLocation->id,
        ]);
        factory(HolidayOpeningHour::class)->create([
            'service_location_id' => $serviceLocation->id,
        ]);
        $user = $this->makeSuperAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/services/{$service->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing((new Service())->getTable(), ['id' => $service->id]);
    }

    /*
     * Refresh service.
     */

    public function test_guest_without_token_cannot_refresh()
    {
        $service = factory(Service::class)->create();

        $response = $this->putJson("/core/v1/services/{$service->id}/refresh");

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function test_guest_with_invalid_token_cannot_refresh()
    {
        $service = factory(Service::class)->create();

        $response = $this->putJson("/core/v1/services/{$service->id}/refresh", [
            'token' => 'invalid-token',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_guest_with_valid_token_can_refresh()
    {
        $now = Date::now();
        Date::setTestNow($now);

        $service = factory(Service::class)->create([
            'last_modified_at' => Date::now()->subMonths(6),
        ]);

        $response = $this->putJson("/core/v1/services/{$service->id}/refresh", [
            'token' => factory(ServiceRefreshToken::class)->create([
                'service_id' => $service->id,
            ])->id,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'last_modified_at' => $now->format(CarbonImmutable::ISO8601),
        ]);
    }

    /*
     * List all the related services.
     */

    public function test_guest_can_list_related()
    {
        $taxonomyOne = Taxonomy::category()->children()->first()->children()->skip(0)->take(1)->first();
        $taxonomyTwo = Taxonomy::category()->children()->first()->children()->skip(1)->take(1)->first();
        $taxonomyThree = Taxonomy::category()->children()->first()->children()->skip(2)->take(1)->first();

        $service = factory(Service::class)->create();
        $service->serviceTaxonomies()->create(['taxonomy_id' => $taxonomyOne->id]);
        $service->serviceTaxonomies()->create(['taxonomy_id' => $taxonomyTwo->id]);
        $service->serviceTaxonomies()->create(['taxonomy_id' => $taxonomyThree->id]);

        $relatedService = factory(Service::class)->create();
        $relatedService->usefulInfos()->create([
            'title' => 'Did You Know?',
            'description' => 'This is a test description',
            'order' => 1,
        ]);
        $relatedService->socialMedias()->create([
            'type' => SocialMedia::TYPE_INSTAGRAM,
            'url' => 'https://www.instagram.com/ayupdigital/',
        ]);
        $relatedService->serviceGalleryItems()->create([
            'file_id' => factory(File::class)->create()->id,
        ]);
        $relatedService->serviceTaxonomies()->create(['taxonomy_id' => $taxonomyOne->id]);
        $relatedService->serviceTaxonomies()->create(['taxonomy_id' => $taxonomyTwo->id]);
        $relatedService->serviceTaxonomies()->create(['taxonomy_id' => $taxonomyThree->id]);

        $unrelatedService = factory(Service::class)->create();
        $unrelatedService->serviceTaxonomies()->create(['taxonomy_id' => $taxonomyOne->id]);
        $unrelatedService->serviceTaxonomies()->create(['taxonomy_id' => $taxonomyTwo->id]);

        $response = $this->json('GET', "/core/v1/services/{$service->id}/related");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure([
            'data' => [
                [
                    'id',
                    'organisation_id',
                    'has_logo',
                    'name',
                    'slug',
                    'type',
                    'status',
                    'intro',
                    'description',
                    'wait_time',
                    'is_free',
                    'fees_text',
                    'fees_url',
                    'testimonial',
                    'video_embed',
                    'url',
                    'contact_name',
                    'contact_phone',
                    'contact_email',
                    'show_referral_disclaimer',
                    'referral_method',
                    'referral_button_text',
                    'referral_email',
                    'referral_url',
                    'criteria' => [
                        'age_group',
                        'disability',
                        'employment',
                        'gender',
                        'housing',
                        'income',
                        'language',
                        'other',
                    ],
                    'useful_infos' => [
                        [
                            'title',
                            'description',
                            'order',
                        ],
                    ],
                    'social_medias' => [
                        [
                            'type',
                            'url',
                        ],
                    ],
                    'score',
                    'gallery_items' => [
                        [
                            'file_id',
                            'url',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                    'category_taxonomies' => [
                        [
                            'id',
                            'parent_id',
                            'name',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                    'last_modified_at',
                    'created_at',
                    'updated_at',
                ],
            ],
            'meta' => [
                'current_page',
                'from',
                'last_page',
                'path',
                'per_page',
                'to',
                'total',
            ],
            'links' => [
                'first',
                'last',
                'prev',
                'next',
            ],
        ]);

        $response->assertJsonFragment(['id' => $relatedService->id]);
        $response->assertJsonMissing(['id' => $unrelatedService->id]);
    }

    /*
     * Get a specific service's logo.
     */

    public function test_guest_can_view_logo()
    {
        $service = factory(Service::class)->create();

        $response = $this->get("/core/v1/services/{$service->id}/logo.png");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'image/png');
    }

    public function test_audit_created_when_logo_viewed()
    {
        $this->fakeEvents();

        $service = factory(Service::class)->create();

        $this->get("/core/v1/services/{$service->id}/logo.png");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($service) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getModel()->id === $service->id);
        });
    }

    /*
     * Upload a specific service's logo.
     */

    public function test_service_admin_can_upload_logo()
    {
        $organisation = factory(Organisation::class)->create();
        $user = factory(User::class)->create();
        $this->makeGlobalAdmin($user);
        $image = Storage::disk('local')->get('/test-data/image.png');

        Passport::actingAs($user);

        $imageResponse = $this->json('POST', '/core/v1/files', [
            'is_private' => false,
            'mime_type' => 'image/png',
            'file' => 'data:image/png;base64,' . base64_encode($image),
        ]);

        $payload = $this->createServicePayload($organisation);
        $payload['status'] = Service::STATUS_ACTIVE;
        $payload['is_national'] = true;
        $payload['show_referral_disclaimer'] = true;
        $payload['referral_method'] = Service::REFERRAL_METHOD_INTERNAL;
        $payload['referral_email'] = $this->faker->safeEmail;
        $payload['category_taxonomies'] = [
            Taxonomy::category()->children()->firstOrFail()->id,
        ];
        $payload['logo_file_id'] = $this->getResponseContent($imageResponse, 'data.id');

        $response = $this->json('POST', '/core/v1/services', $payload);
        $serviceId = $this->getResponseContent($response, 'data.id');

        $response->assertStatus(Response::HTTP_CREATED);
        $this->assertDatabaseHas(table(Service::class), [
            'id' => $serviceId,
        ]);
        $this->assertDatabaseMissing(table(Service::class), [
            'id' => $serviceId,
            'logo_file_id' => null,
        ]);
    }

    /*
     * Delete a specific service's logo.
     */

    public function test_service_admin_can_delete_logo()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeGlobalAdmin($user);
        $service = factory(Service::class)->create([
            'logo_file_id' => factory(File::class)->create()->id,
        ]);

        Passport::actingAs($user);

        $payload = $this->updateServicePayload($service);
        $payload['logo_file_id'] = null;

        $response = $this->json('PUT', "/core/v1/services/{$service->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'has_logo' => false,
        ]);
    }

    /*
     * Get a specific service's gallery item.
     */

    public function test_guest_can_view_gallery_item()
    {
        /** @var \App\Models\File $file */
        $file = factory(File::class)->create([
            'filename' => 'random-name.png',
            'mime_type' => 'image/png',
        ])->upload(
            Storage::disk('local')->get('/test-data/image.png')
        );

        /** @var \App\Models\Service $service */
        $service = factory(Service::class)->create();

        /** @var \App\Models\ServiceGalleryItem $serviceGalleryItem */
        $serviceGalleryItem = $service->serviceGalleryItems()->create([
            'file_id' => $file->id,
        ]);

        $response = $this->get($serviceGalleryItem->url());

        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'image/png');
    }

    /**
     * Bulk import organisations
     */

    public function test_guest_cannot_bulk_import()
    {
        Storage::fake('local');

        $data = [
            'spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(base_path('tests/assets/services_import_1_good.xls'))),
        ];
        $response = $this->json('POST', "/core/v1/services/import", $data);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_bulk_import()
    {
        Storage::fake('local');

        $service = factory(Service::class)->create();
        $user = $this->makeServiceWorker(factory(User::class)->create(), $service);

        Passport::actingAs($user);

        $data = [
            'spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(base_path('tests/assets/services_import_1_good.xls'))),
            'organisation_id' => $service->organisation_id,
        ];

        $response = $this->json('POST', "/core/v1/services/import", $data);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_bulk_import()
    {
        Storage::fake('local');

        $service = factory(Service::class)->create();
        $user = $this->makeServiceAdmin(factory(User::class)->create(), $service);

        Passport::actingAs($user);

        $data = [
            'spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(base_path('tests/assets/services_import_1_good.xls'))),
            'organisation_id' => $service->organisation_id,
        ];

        $response = $this->json('POST', "/core/v1/services/import", $data);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_from_other_organisation_cannot_bulk_import()
    {
        Storage::fake('local');

        $organisation1 = factory(Organisation::class)->create();
        $organisation2 = factory(Organisation::class)->create();
        $user = $this->makeOrganisationAdmin(factory(User::class)->create(), $organisation1);

        Passport::actingAs($user);

        $data = [
            'spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(base_path('tests/assets/services_import_1_good.xls'))),
            'organisation_id' => $organisation2->id,
        ];

        $response = $this->json('POST', "/core/v1/services/import", $data);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_can_bulk_import()
    {
        Storage::fake('local');

        $organisation = factory(Organisation::class)->create();
        $user = $this->makeOrganisationAdmin(factory(User::class)->create(), $organisation);

        Passport::actingAs($user);

        $data = [
            'spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(base_path('tests/assets/services_import_1_good.xls'))),
            'organisation_id' => $organisation->id,
        ];

        $response = $this->json('POST', "/core/v1/services/import", $data);

        $response->assertStatus(Response::HTTP_CREATED);
    }

    public function test_global_admin_can_bulk_import()
    {
        Storage::fake('local');

        $data = [
            'spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(base_path('tests/assets/services_import_1_good.xls'))),
            'organisation_id' => factory(Organisation::class)->create()->id,
        ];
        $user = $this->makeGlobalAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $response = $this->json('POST', "/core/v1/services/import", $data);

        $response->assertStatus(Response::HTTP_CREATED);
    }

    public function test_super_admin_can_bulk_import()
    {
        Storage::fake('local');

        $data = [
            'spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(base_path('tests/assets/services_import_1_good.xls'))),
            'organisation_id' => factory(Organisation::class)->create()->id,
        ];

        $user = $this->makeSuperAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $response = $this->json('POST', "/core/v1/services/import", $data);

        $response->assertStatus(Response::HTTP_CREATED);
    }

    public function test_validate_file_import_type()
    {
        Storage::fake('local');

        $organisation = factory(Organisation::class)->create();

        $invalidFieldTypes = [
            ['spreadsheet' => 'This is a string'],
            ['spreadsheet' => 1],
            ['spreadsheet' => ['foo' => 'bar']],
            ['spreadsheet' => UploadedFile::fake()->create('dummy.doc', 3000)],
            ['spreadsheet' => UploadedFile::fake()->create('dummy.txt', 3000)],
            ['spreadsheet' => UploadedFile::fake()->create('dummy.csv', 3000)],
        ];

        $user = $this->makeSuperAdmin(factory(User::class)->create());
        $organisation = factory(Organisation::class)->create();

        Passport::actingAs($user);

        foreach ($invalidFieldTypes as $data) {
            $data['organisation_id'] = $organisation->id;
            $response = $this->json('POST', "/core/v1/services/import", $data);
            $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $response = $this->json('POST', "/core/v1/services/import", [
            'spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(base_path('tests/assets/services_import_1_good.xls'))),
            'organisation_id' => $organisation->id,
        ]);
        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJson([
            'data' => [
                'imported_row_count' => 1,
            ],
        ]);

        $response = $this->json('POST', "/core/v1/services/import", [
            'spreadsheet' => 'data:application/octet-stream;base64,' . base64_encode(file_get_contents(base_path('tests/assets/services_import_1_good.xls'))),
            'organisation_id' => $organisation->id,
        ]);
        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJson([
            'data' => [
                'imported_row_count' => 1,
            ],
        ]);

        $response = $this->json('POST', "/core/v1/services/import", [
            'spreadsheet' => 'data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,' . base64_encode(file_get_contents(base_path('tests/assets/services_import_1_good.xlsx'))),
            'organisation_id' => $organisation->id,
        ]);
        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJson([
            'data' => [
                'imported_row_count' => 1,
            ],
        ]);
    }

    public function test_validate_file_import_service_fields()
    {
        Storage::fake('local');

        $organisation = factory(Organisation::class)->create();
        $user = $this->makeOrganisationAdmin(factory(User::class)->create(), $organisation);

        Passport::actingAs($user);

        $response = $this->json('POST', "/core/v1/services/import", [
            'spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(base_path('tests/assets/services_import_3_bad.xls'))),
            'organisation_id' => $organisation->id,
        ]);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJson([
            'data' => [
                'errors' => [
                    'spreadsheet' => [
                        [
                            'row' => [],
                            'errors' => [
                                'name' => [],
                                'type' => [],
                                'status' => [],
                                'intro' => [],
                                'description' => [],
                                'is_free' => [],
                                'url' => [],
                                'show_referral_disclaimer' => [],
                                'referral_method' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $response = $this->json('POST', "/core/v1/services/import", [
            'spreadsheet' => 'data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,' . base64_encode(file_get_contents(base_path('tests/assets/services_import_3_bad.xlsx'))),
            'organisation_id' => $organisation->id,
        ]);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJson([
            'data' => [
                'errors' => [
                    'spreadsheet' => [
                        [
                            'row' => [],
                            'errors' => [
                                'name' => [],
                                'type' => [],
                                'status' => [],
                                'intro' => [],
                                'description' => [],
                                'is_free' => [],
                                'url' => [],
                                'show_referral_disclaimer' => [],
                                'referral_method' => [],
                            ],
                        ],
                        [
                            'row' => [],
                            'errors' => [
                                'referral_email' => [],
                            ],
                        ],
                        [
                            'row' => [],
                            'errors' => [
                                'referral_url' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function test_validate_file_import_service_field_global_admin_permissions()
    {
        Storage::fake('local');

        $organisation = factory(Organisation::class)->create();
        $organisationAdminUser = $this->makeOrganisationAdmin(factory(User::class)->create(), $organisation);
        $globalAdminUser = $this->makeGlobalAdmin(factory(User::class)->create());

        Passport::actingAs($organisationAdminUser);

        $response = $this->json('POST', "/core/v1/services/import", [
            'spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(base_path('tests/assets/services_import_1_requires_global.xls'))),
            'organisation_id' => $organisation->id,
        ]);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJson([
            'data' => [
                'errors' => [
                    'spreadsheet' => [
                        [
                            'row' => [],
                            'errors' => [
                                'status' => [],
                                'referral_method' => [],
                                'referral_button_text' => [],
                                'referral_email' => [],
                                'referral_url' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $response = $this->json('POST', "/core/v1/services/import", [
            'spreadsheet' => 'data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,' . base64_encode(file_get_contents(base_path('tests/assets/services_import_1_requires_global.xlsx'))),
            'organisation_id' => $organisation->id,
        ]);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJson([
            'data' => [
                'errors' => [
                    'spreadsheet' => [
                        [
                            'row' => [],
                            'errors' => [
                                'status' => [],
                                'referral_method' => [],
                                'referral_button_text' => [],
                                'referral_email' => [],
                                'referral_url' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        Passport::actingAs($globalAdminUser);

        $response = $this->json('POST', "/core/v1/services/import", [
            'spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(base_path('tests/assets/services_import_1_requires_global.xls'))),
            'organisation_id' => $organisation->id,
        ]);
        $response->assertStatus(Response::HTTP_CREATED);

        $response = $this->json('POST', "/core/v1/services/import", [
            'spreadsheet' => 'data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,' . base64_encode(file_get_contents(base_path('tests/assets/services_import_1_requires_global.xlsx'))),
            'organisation_id' => $organisation->id,
        ]);
        $response->assertStatus(Response::HTTP_CREATED);
    }

    public function test_validate_file_import_service_field_super_admin_permissions()
    {
        Storage::fake('local');

        $organisation = factory(Organisation::class)->create();
        $globalAdminUser = $this->makeGlobalAdmin(factory(User::class)->create());
        $superAdminUser = $this->makeSuperAdmin(factory(User::class)->create());

        Passport::actingAs($globalAdminUser);

        $response = $this->json('POST', "/core/v1/services/import", [
            'spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(base_path('tests/assets/services_import_1_requires_super.xls'))),
            'organisation_id' => $organisation->id,
        ]);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJson([
            'data' => [
                'errors' => [
                    'spreadsheet' => [
                        [
                            'row' => [],
                            'errors' => [
                                'show_referral_disclaimer' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $response = $this->json('POST', "/core/v1/services/import", [
            'spreadsheet' => 'data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,' . base64_encode(file_get_contents(base_path('tests/assets/services_import_1_requires_super.xlsx'))),
            'organisation_id' => $organisation->id,
        ]);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJson([
            'data' => [
                'errors' => [
                    'spreadsheet' => [
                        [
                            'row' => [],
                            'errors' => [
                                'show_referral_disclaimer' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        Passport::actingAs($superAdminUser);

        $response = $this->json('POST', "/core/v1/services/import", [
            'spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(base_path('tests/assets/services_import_1_requires_super.xls'))),
            'organisation_id' => $organisation->id,
        ]);
        $response->assertStatus(Response::HTTP_CREATED);

        $response = $this->json('POST', "/core/v1/services/import", [
            'spreadsheet' => 'data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,' . base64_encode(file_get_contents(base_path('tests/assets/services_import_1_requires_super.xlsx'))),
            'organisation_id' => $organisation->id,
        ]);
        $response->assertStatus(Response::HTTP_CREATED);
    }

    public function test_services_file_import_100rows()
    {
        Storage::fake('local');

        $organisation = factory(Organisation::class)->create();
        $user = $this->makeOrganisationAdmin(factory(User::class)->create(), $organisation);

        Passport::actingAs($user);

        $response = $this->json('POST', "/core/v1/services/import", [
            'spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(base_path('tests/assets/services_import_100_good.xls'))),
            'organisation_id' => $organisation->id,
        ]);
        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJson([
            'data' => [
                'imported_row_count' => 100,
            ],
        ]);

        $response = $this->json('POST', "/core/v1/services/import", [
            'spreadsheet' => 'data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,' . base64_encode(file_get_contents(base_path('tests/assets/services_import_100_good.xlsx'))),
            'organisation_id' => $organisation->id,
        ]);
        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJson([
            'data' => [
                'imported_row_count' => 100,
            ],
        ]);
    }

    /**
     * @group slow
     */
    public function test_services_file_import_5krows()
    {
        Storage::fake('local');

        $organisation = factory(Organisation::class)->create();
        $user = $this->makeOrganisationAdmin(factory(User::class)->create(), $organisation);

        Passport::actingAs($user);

        $response = $this->json('POST', "/core/v1/services/import", [
            'spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(base_path('tests/assets/services_import_5000_good.xls'))),
            'organisation_id' => $organisation->id]);
        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJson([
            'data' => [
                'imported_row_count' => 5000,
            ],
        ]);

        $response = $this->json('POST', "/core/v1/services/import", [
            'spreadsheet' => 'data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,' . base64_encode(file_get_contents(base_path('tests/assets/services_import_5000_good.xlsx'))),
            'organisation_id' => $organisation->id,
        ]);
        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJson([
            'data' => [
                'imported_row_count' => 5000,
            ],
        ]);
    }

    public function test_service_criterions_created_on_import()
    {
        Storage::fake('local');

        $organisation = factory(Organisation::class)->create();
        $user = $this->makeOrganisationAdmin(factory(User::class)->create(), $organisation);

        Passport::actingAs($user);

        $response = $this->json('POST', "/core/v1/services/import", [
            'spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(base_path('tests/assets/services_import_1_good.xls'))),
            'organisation_id' => $organisation->id,
        ]);
        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJson([
            'data' => [
                'imported_row_count' => 1,
            ],
        ]);

        $serviceId = \DB::table('services')->latest()->pluck('id');

        $this->assertDatabaseHas('service_criteria', [
            'service_id' => $serviceId,
        ]);
    }
    public function test_organisation_admin_can_create_an_app_service_with_one_app_store_url()
    {
        $organisation = factory(Organisation::class)->create();
        $user = $this->makeOrganisationAdmin(factory(User::class)->create(), $organisation);

        Passport::actingAs($user);

        $payload = $this->createServicePayload($organisation);
        $payload['type'] = Service::TYPE_APP;
        $payload['ios_app_url'] = $this->faker->url;

        $response = $this->json('POST', '/core/v1/services', $payload);
        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonFragment($payload);
    }

    public function test_organisation_admin_cannot_create_an_app_service_without_an_app_store_url()
    {
        $organisation = factory(Organisation::class)->create();
        $user = $this->makeOrganisationAdmin(factory(User::class)->create(), $organisation);

        Passport::actingAs($user);

        $payload = $this->createServicePayload($organisation);
        $payload['type'] = Service::TYPE_APP;

        $response = $this->json('POST', '/core/v1/services', $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_organisation_admin_cannot_create_an_app_service_without_a_valid_app_store_url()
    {
        $organisation = factory(Organisation::class)->create();
        $user = $this->makeOrganisationAdmin(factory(User::class)->create(), $organisation);

        Passport::actingAs($user);

        $payload = $this->createServicePayload($organisation);
        $payload['type'] = Service::TYPE_APP;
        $payload['ios_app_url'] = 'www.example.com';

        $response = $this->json('POST', '/core/v1/services', $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
