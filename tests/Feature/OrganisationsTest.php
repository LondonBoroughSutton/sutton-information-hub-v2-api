<?php

namespace Tests\Feature;

use App\Events\EndpointHit;
use App\Models\Audit;
use App\Models\Location;
use App\Models\Organisation;
use App\Models\Role;
use App\Models\Service;
use App\Models\SocialMedia;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\TestCase;

class OrganisationsTest extends TestCase
{
    /**
     * Create spreadsheets of organisations
     *
     * @param Array $organisations
     * @return null
     **/
    public function createOrganisationSpreadsheets($organisations)
    {
        $headers = [
            'name',
            'description',
            'url',
            'email',
            'phone',
        ];

        $spreadsheet = \Tests\Integration\SpreadsheetParserTest::createSpreadsheets(collect($organisations), $headers);
        \Tests\Integration\SpreadsheetParserTest::writeSpreadsheetsToDisk($spreadsheet, 'test.xlsx', 'test.xls');
    }
    /*
     * List all the organisations.
     */

    public function test_guest_can_list_them()
    {
        $organisation = factory(Organisation::class)->create();

        $response = $this->json('GET', '/core/v1/organisations');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            [
                'id' => $organisation->id,
                'location_id' => $organisation->location_id,
                'has_logo' => $organisation->hasLogo(),
                'slug' => $organisation->slug,
                'name' => $organisation->name,
                'description' => $organisation->description,
                'url' => $organisation->url,
                'email' => $organisation->email,
                'phone' => $organisation->phone,
                'social_medias' => [],
                'location' => null,
                'created_at' => $organisation->created_at->format(CarbonImmutable::ISO8601),
                'updated_at' => $organisation->updated_at->format(CarbonImmutable::ISO8601),
            ],
        ]);
    }

    public function test_audit_created_when_listed()
    {
        $this->fakeEvents();

        $this->json('GET', '/core/v1/organisations');

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) {
            return ($event->getAction() === Audit::ACTION_READ);
        });
    }

    public function test_guest_can_sort_by_name()
    {
        $organisationOne = factory(Organisation::class)->create([
            'name' => 'Organisation A',
        ]);
        $organisationTwo = factory(Organisation::class)->create([
            'name' => 'Organisation B',
        ]);

        $response = $this->json('GET', '/core/v1/organisations?sort=-name');
        $data = $this->getResponseContent($response);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertEquals($organisationOne->id, $data['data'][1]['id']);
        $this->assertEquals($organisationTwo->id, $data['data'][0]['id']);
    }

    public function test_guest_can_filter_by_has_email()
    {
        /** @var \App\Models\Organisation $organisation */
        $organisation = factory(Organisation::class)->create([
            'email' => 'acme.org@example.com',
        ]);
        factory(Organisation::class)->create([
            'email' => null,
        ]);

        $response = $this->json('GET', '/core/v1/organisations?filter[has_email]=true');
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['id' => $organisation->id]);
    }

    public function test_guest_can_filter_by_has_social_medias()
    {
        /** @var \App\Models\Organisation $organisation */
        $organisation = factory(Organisation::class)->create();
        $organisation->socialMedias()->create([
            'type' => SocialMedia::TYPE_FACEBOOK,
            'url' => 'https://facebook.com/AcmeOrg',
        ]);

        factory(Organisation::class)->create();

        $response = $this->json('GET', '/core/v1/organisations?filter[has_social_medias]=true');
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['id' => $organisation->id]);
    }

    public function test_guest_can_filter_by_has_phone()
    {
        /** @var \App\Models\Organisation $organisation */
        $organisation = factory(Organisation::class)->create([
            'phone' => '01130000000',
        ]);
        factory(Organisation::class)->create([
            'phone' => null,
        ]);

        $response = $this->json('GET', '/core/v1/organisations?filter[has_phone]=true');
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['id' => $organisation->id]);
    }

    public function test_guest_can_filter_by_has_services()
    {
        /** @var \App\Models\Organisation $organisation */
        $organisation = factory(Organisation::class)->create();
        factory(Service::class)->create([
            'organisation_id' => $organisation->id,
        ]);

        factory(Organisation::class)->create();

        $response = $this->json('GET', '/core/v1/organisations?filter[has_services]=true');
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['id' => $organisation->id]);
    }

    /*
     * Create an organisation.
     */

    public function test_guest_cannot_create_one()
    {
        $response = $this->json('POST', '/core/v1/organisations');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_create_one()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = factory(Service::class)->create();
        $user = factory(User::class)->create();
        $user->makeServiceWorker($service);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/organisations');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_create_one()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = factory(Service::class)->create();
        $user = factory(User::class)->create();
        $user->makeServiceAdmin($service);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/organisations');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_cannot_create_one()
    {
        /**
         * @var \App\Models\Organisation $organisation
         * @var \App\Models\User $user
         */
        $organisation = factory(Organisation::class)->create();
        $user = factory(User::class)->create();
        $user->makeOrganisationAdmin($organisation);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/organisations');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_global_admin_can_create_one()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $user->makeGlobalAdmin();
        $payload = [
            'slug' => 'test-org',
            'name' => 'Test Org',
            'description' => 'Test description',
            'url' => 'http://test-org.example.com',
            'email' => 'info@test-org.example.com',
            'phone' => '07700000000',
        ];

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/organisations', $payload);
        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonFragment($payload);
    }

    public function test_global_admin_can_create_one_with_minimal_fields()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $user->makeGlobalAdmin();
        $payload = [
            'slug' => 'test-org',
            'name' => 'Test Org',
            'description' => 'Test description',
            'url' => null,
            'email' => null,
            'phone' => null,
        ];

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/organisations', $payload);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonFragment($payload);
    }

    public function test_global_admin_cannot_create_one_with_no_description()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $user->makeGlobalAdmin();
        $payload = [
            'slug' => 'test-org',
            'name' => 'Test Org',
            'description' => null,
            'url' => null,
            'email' => null,
            'phone' => null,
        ];

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/organisations', $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_audit_created_when_created()
    {
        $this->fakeEvents();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $user->makeGlobalAdmin();

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/organisations', [
            'slug' => 'test-org',
            'name' => 'Test Org',
            'description' => 'Test description',
            'url' => 'http://test-org.example.com',
            'email' => 'info@test-org.example.com',
            'phone' => '07700000000',
        ]);

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $response) {
            return ($event->getAction() === Audit::ACTION_CREATE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $this->getResponseContent($response)['data']['id']);
        });
    }

    /*
     * Get a specific organisation.
     */

    public function test_guest_can_view_one()
    {
        $organisation = factory(Organisation::class)->create();

        $response = $this->json('GET', "/core/v1/organisations/{$organisation->id}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            [
                'id' => $organisation->id,
                'location_id' => $organisation->location_id,
                'has_logo' => $organisation->hasLogo(),
                'slug' => $organisation->slug,
                'name' => $organisation->name,
                'description' => $organisation->description,
                'url' => $organisation->url,
                'email' => $organisation->email,
                'phone' => $organisation->phone,
                'social_medias' => [],
                'location' => null,
                'created_at' => $organisation->created_at->format(CarbonImmutable::ISO8601),
                'updated_at' => $organisation->updated_at->format(CarbonImmutable::ISO8601),
            ],
        ]);
    }

    public function test_guest_can_view_one_by_slug()
    {
        $organisation = factory(Organisation::class)->create();

        $response = $this->json('GET', "/core/v1/organisations/{$organisation->slug}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            [
                'id' => $organisation->id,
                'location_id' => $organisation->location_id,
                'has_logo' => $organisation->hasLogo(),
                'slug' => $organisation->slug,
                'name' => $organisation->name,
                'description' => $organisation->description,
                'url' => $organisation->url,
                'email' => $organisation->email,
                'phone' => $organisation->phone,
                'social_medias' => [],
                'location' => null,
                'created_at' => $organisation->created_at->format(CarbonImmutable::ISO8601),
                'updated_at' => $organisation->updated_at->format(CarbonImmutable::ISO8601),
            ],
        ]);
    }

    public function test_audit_created_when_viewed()
    {
        $this->fakeEvents();

        $organisation = factory(Organisation::class)->create();

        $this->json('GET', "/core/v1/organisations/{$organisation->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($organisation) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getModel()->id === $organisation->id);
        });
    }

    /*
     * Update a specific organisation.
     */

    public function test_guest_cannot_update_one()
    {
        $organisation = factory(Organisation::class)->create();

        $response = $this->json('PUT', "/core/v1/organisations/{$organisation->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_update_one()
    {
        $service = factory(Service::class)->create();
        $user = factory(User::class)->create()->makeServiceWorker($service);
        $organisation = factory(Organisation::class)->create();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/organisations/{$organisation->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_update_one()
    {
        $service = factory(Service::class)->create();
        $user = factory(User::class)->create()->makeServiceAdmin($service);
        $organisation = factory(Organisation::class)->create();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/organisations/{$organisation->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_can_update_one()
    {
        $organisation = factory(Organisation::class)->create();
        $user = factory(User::class)->create()->makeOrganisationAdmin($organisation);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/organisations/{$organisation->id}", [
            'slug' => 'test-org',
            'name' => 'Test Org',
            'description' => 'Test description',
            'url' => 'http://test-org.example.com',
            'email' => 'info@test-org.example.com',
            'phone' => '07700000000',
            'location_id' => null,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'slug' => 'test-org',
            'name' => 'Test Org',
            'description' => 'Test description',
            'url' => 'http://test-org.example.com',
            'email' => 'info@test-org.example.com',
            'phone' => '07700000000',
            'location_id' => null,
        ]);
    }

    public function test_organisation_admin_can_update_with_minimal_fields()
    {
        $organisation = factory(Organisation::class)->create();
        $user = factory(User::class)->create()->makeOrganisationAdmin($organisation);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/organisations/{$organisation->id}", [
            'slug' => 'test-org',
            'name' => 'Test Org',
            'description' => 'Test description',
            'url' => null,
            'email' => null,
            'phone' => null,
            'location_id' => null,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'slug' => 'test-org',
            'name' => 'Test Org',
            'description' => 'Test description',
            'url' => null,
            'email' => null,
            'phone' => null,
            'location_id' => null,
        ]);
    }

    public function test_organisation_admin_cannot_update_with_no_description()
    {
        $organisation = factory(Organisation::class)->create([
            'email' => 'info@test-org.example.com',
            'phone' => null,
        ]);
        $user = factory(User::class)->create()->makeOrganisationAdmin($organisation);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/organisations/{$organisation->id}", [
            'slug' => 'test-org',
            'name' => 'Test Org',
            'description' => null,
            'url' => null,
            'email' => null,
            'phone' => null,
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_only_partial_fields_can_be_updated()
    {
        $organisation = factory(Organisation::class)->create();
        $user = factory(User::class)->create()->makeOrganisationAdmin($organisation);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/organisations/{$organisation->id}", [
            'slug' => 'test-org',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'slug' => 'test-org',
        ]);
    }

    public function test_audit_created_when_updated()
    {
        $this->fakeEvents();

        $organisation = factory(Organisation::class)->create();
        $user = factory(User::class)->create()->makeOrganisationAdmin($organisation);

        Passport::actingAs($user);

        $this->json('PUT', "/core/v1/organisations/{$organisation->id}", [
            'slug' => 'test-org',
            'name' => 'Test Org',
            'description' => 'Test description',
            'url' => 'http://test-org.example.com',
            'email' => 'info@test-org.example.com',
            'phone' => '07700000000',
        ]);

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $organisation) {
            return ($event->getAction() === Audit::ACTION_UPDATE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $organisation->id);
        });
    }

    public function test_organisation_admin_can_add_social_media()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $user->makeGlobalAdmin();
        $organisation = factory(Organisation::class)->create();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/organisations/{$organisation->id}", [
            'social_medias' => [
                [
                    'type' => SocialMedia::TYPE_INSTAGRAM,
                    'url' => 'https://www.instagram.com/ayupdigital',
                ],
            ],
        ]);
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'social_medias' => [
                [
                    'type' => SocialMedia::TYPE_INSTAGRAM,
                    'url' => 'https://www.instagram.com/ayupdigital',
                ],
            ],
        ]);

        $this->assertDatabaseHas(table(SocialMedia::class), [
            'sociable_id' => $organisation->id,
            'sociable_type' => 'organisations',
        ]);
    }

    public function test_organisation_admin_can_remove_social_media()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $user->makeGlobalAdmin();
        $organisation = factory(Organisation::class)->states('social')->create();
        $social = $organisation->socialMedias->all();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/organisations/{$organisation->id}", [
            'social_medias' => [
                [
                    'type' => $social[1]->type,
                    'url' => $social[1]->url,
                ],
                [
                    'type' => $social[2]->type,
                    'url' => $social[2]->url,
                ],
            ],
        ]);
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'social_medias' => [
                [
                    'type' => $social[1]->type,
                    'url' => $social[1]->url,
                ],
                [
                    'type' => $social[2]->type,
                    'url' => $social[2]->url,
                ],
            ],
        ]);

        $this->assertDatabaseHas(table(SocialMedia::class), [
            'sociable_id' => $organisation->id,
            'sociable_type' => 'organisations',
            'url' => $social[1]->url,
        ]);

        $this->assertDatabaseHas(table(SocialMedia::class), [
            'sociable_id' => $organisation->id,
            'sociable_type' => 'organisations',
            'url' => $social[2]->url,
        ]);

        $this->assertDatabaseMissing(table(SocialMedia::class), [
            'sociable_id' => $organisation->id,
            'sociable_type' => 'organisations',
            'url' => $social[0]->url,
        ]);
    }

    public function test_organisation_admin_can_add_address()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $user->makeGlobalAdmin();
        $organisation = factory(Organisation::class)->create();
        $location = factory(Location::class)->create();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/organisations/{$organisation->id}", [
            'location_id' => $location->id,
        ]);
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'location' => [
                'id' => $location->id,
                'has_image' => $location->hasImage(),
                'address_line_1' => $location->address_line_1,
                'address_line_2' => $location->address_line_2,
                'address_line_3' => $location->address_line_3,
                'city' => $location->city,
                'county' => $location->county,
                'postcode' => $location->postcode,
                'country' => $location->country,
                'lat' => $location->lat,
                'lon' => $location->lon,
                'accessibility_info' => $location->accessibility_info,
                'has_wheelchair_access' => $location->has_wheelchair_access,
                'has_induction_loop' => $location->has_induction_loop,
                'created_at' => $location->created_at->format(CarbonImmutable::ISO8601),
                'updated_at' => $location->updated_at->format(CarbonImmutable::ISO8601),
            ],
        ]);

        $this->assertDatabaseHas(table(Organisation::class), [
            'id' => $organisation->id,
            'location_id' => $location->id,
        ]);
    }

    public function test_organisation_admin_can_update_address()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $user->makeGlobalAdmin();
        $organisation = factory(Organisation::class)->states('location')->create();
        $location = factory(Location::class)->create();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/organisations/{$organisation->id}", [
            'location_id' => $location->id,
        ]);
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'location' => [
                'id' => $location->id,
                'has_image' => $location->hasImage(),
                'address_line_1' => $location->address_line_1,
                'address_line_2' => $location->address_line_2,
                'address_line_3' => $location->address_line_3,
                'city' => $location->city,
                'county' => $location->county,
                'postcode' => $location->postcode,
                'country' => $location->country,
                'lat' => $location->lat,
                'lon' => $location->lon,
                'accessibility_info' => $location->accessibility_info,
                'has_wheelchair_access' => $location->has_wheelchair_access,
                'has_induction_loop' => $location->has_induction_loop,
                'created_at' => $location->created_at->format(CarbonImmutable::ISO8601),
                'updated_at' => $location->updated_at->format(CarbonImmutable::ISO8601),
            ],
        ]);

        $this->assertDatabaseHas(table(Organisation::class), [
            'id' => $organisation->id,
            'location_id' => $location->id,
        ]);
    }

    public function test_organisation_admin_can_delete_address()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $user->makeGlobalAdmin();
        $organisation = factory(Organisation::class)->states('location')->create();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/organisations/{$organisation->id}", [
            'location_id' => null,
        ]);
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'location' => null,
        ]);

        $this->assertDatabaseHas(table(Organisation::class), [
            'id' => $organisation->id,
            'location_id' => null,
        ]);
    }

    /*
     * Delete a specific organisation.
     */

    public function test_guest_cannot_delete_one()
    {
        $organisation = factory(Organisation::class)->create();

        $response = $this->json('DELETE', "/core/v1/organisations/{$organisation->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_delete_one()
    {
        $service = factory(Service::class)->create();
        $user = factory(User::class)->create()->makeServiceWorker($service);
        $organisation = factory(Organisation::class)->create();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/organisations/{$organisation->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_delete_one()
    {
        $service = factory(Service::class)->create();
        $user = factory(User::class)->create()->makeServiceAdmin($service);
        $organisation = factory(Organisation::class)->create();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/organisations/{$organisation->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_cannot_delete_one()
    {
        $organisation = factory(Organisation::class)->create();
        $user = factory(User::class)->create()->makeOrganisationAdmin($organisation);

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/organisations/{$organisation->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_global_admin_cannot_delete_one()
    {
        $organisation = factory(Organisation::class)->create();
        $user = factory(User::class)->create()->makeGlobalAdmin();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/organisations/{$organisation->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_super_admin_can_delete_one()
    {
        $organisation = factory(Organisation::class)->create();
        $user = factory(User::class)->create()->makeSuperAdmin();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/organisations/{$organisation->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing((new Organisation())->getTable(), ['id' => $organisation->id]);
    }

    public function test_audit_created_when_deleted()
    {
        $this->fakeEvents();

        $organisation = factory(Organisation::class)->create();
        $user = factory(User::class)->create()->makeSuperAdmin();

        Passport::actingAs($user);

        $this->json('DELETE', "/core/v1/organisations/{$organisation->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $organisation) {
            return ($event->getAction() === Audit::ACTION_DELETE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $organisation->id);
        });
    }

    /*
     * Get a specific organisation's logo.
     */

    public function test_guest_can_view_logo()
    {
        $organisation = factory(Organisation::class)->create();

        $response = $this->get("/core/v1/organisations/{$organisation->id}/logo.png");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'image/png');
    }

    public function test_audit_created_when_logo_viewed()
    {
        $this->fakeEvents();

        $organisation = factory(Organisation::class)->create();

        $this->get("/core/v1/organisations/{$organisation->id}/logo.png");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($organisation) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getModel()->id === $organisation->id);
        });
    }

    /*
     * Upload a specific organisation's logo.
     */

    public function test_organisation_admin_can_upload_logo()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $user->makeGlobalAdmin();
        $image = Storage::disk('local')->get('/test-data/image.png');

        Passport::actingAs($user);

        $imageResponse = $this->json('POST', '/core/v1/files', [
            'is_private' => false,
            'mime_type' => 'image/png',
            'file' => 'data:image/png;base64,' . base64_encode($image),
        ]);

        $response = $this->json('POST', '/core/v1/organisations', [
            'slug' => 'test-org',
            'name' => 'Test Org',
            'description' => 'Test description',
            'url' => 'http://test-org.example.com',
            'email' => 'info@test-org.example.com',
            'phone' => '07700000000',
            'logo_file_id' => $this->getResponseContent($imageResponse, 'data.id'),
        ]);
        $organisationId = $this->getResponseContent($response, 'data.id');

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonFragment([
            'id' => $organisationId,
            'slug' => 'test-org',
            'name' => 'Test Org',
            'description' => 'Test description',
            'url' => 'http://test-org.example.com',
            'email' => 'info@test-org.example.com',
            'phone' => '07700000000',
            'has_logo' => true,
        ]);
    }

    /*
     * Delete a specific organisation's logo.
     */

    public function test_organisation_admin_can_delete_logo()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $user->makeGlobalAdmin();
        $organisation = factory(Organisation::class)->states('web', 'email', 'phone', 'logo')->create();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/organisations/{$organisation->id}", [
            'slug' => $organisation->slug,
            'name' => $organisation->name,
            'description' => $organisation->description,
            'url' => $organisation->url,
            'email' => $organisation->email,
            'phone' => $organisation->phone,
            'logo_file_id' => null,
        ]);
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $organisation->id,
            'slug' => $organisation->slug,
            'name' => $organisation->name,
            'description' => $organisation->description,
            'url' => $organisation->url,
            'email' => $organisation->email,
            'phone' => $organisation->phone,
            'has_logo' => false,
        ]);
    }

    /**
     * Bulk import organisations
     */

    public function test_guest_cannot_bulk_import()
    {
        Storage::fake('local');

        $organisations = factory(Organisation::class, 2)->states('web', 'email', 'phone')->make();

        $this->createOrganisationSpreadsheets($organisations);

        $data = [
            'spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xls'))),
        ];
        $response = $this->json('POST', "/core/v1/organisations/import", $data);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_bulk_import()
    {
        Storage::fake('local');

        $organisations = factory(Organisation::class, 2)->states('web', 'email', 'phone')->make();

        $this->createOrganisationSpreadsheets($organisations);

        $data = [
            'spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xls'))),
        ];

        $service = factory(Service::class)->create();
        $user = factory(User::class)->create()->makeServiceWorker($service);

        Passport::actingAs($user);

        $response = $this->json('POST', "/core/v1/organisations/import", $data);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_bulk_import()
    {
        Storage::fake('local');

        $organisations = factory(Organisation::class, 2)->states('web', 'email', 'phone')->make();

        $this->createOrganisationSpreadsheets($organisations);

        $data = [
            'spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xls'))),
        ];

        $service = factory(Service::class)->create();
        $user = factory(User::class)->create()->makeServiceAdmin($service);

        Passport::actingAs($user);

        $response = $this->json('POST', "/core/v1/organisations/import", $data);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_cannot_bulk_import()
    {
        Storage::fake('local');

        $organisations = factory(Organisation::class, 2)->states('web', 'email', 'phone')->make();

        $this->createOrganisationSpreadsheets($organisations);

        $data = [
            'spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xls'))),
        ];
        $organisation = factory(Organisation::class)->create();
        $user = factory(User::class)->create()->makeOrganisationAdmin($organisation);

        Passport::actingAs($user);

        $response = $this->json('POST', "/core/v1/organisations/import", $data);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_global_admin_cannot_bulk_import()
    {
        Storage::fake('local');

        $organisations = factory(Organisation::class, 2)->states('web', 'email', 'phone')->make();

        $this->createOrganisationSpreadsheets($organisations);

        $data = [
            'spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xls'))),
        ];
        $user = factory(User::class)->create()->makeGlobalAdmin();

        Passport::actingAs($user);

        $response = $this->json('POST', "/core/v1/organisations/import", $data);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_super_admin_can_bulk_import()
    {
        Storage::fake('local');

        $organisations = factory(Organisation::class, 2)->states('web', 'email', 'phone')->make();

        $this->createOrganisationSpreadsheets($organisations);

        $data = [
            'spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xls'))),
        ];

        $user = factory(User::class)->create()->makeSuperAdmin();

        Passport::actingAs($user);

        $response = $this->json('POST', "/core/v1/organisations/import", $data);

        $response->assertStatus(Response::HTTP_CREATED);
    }

    public function test_validate_file_import_type()
    {
        Storage::fake('local');

        $invalidFieldTypes = [
            ['spreadsheet' => 'This is a string'],
            ['spreadsheet' => 1],
            ['spreadsheet' => ['foo' => 'bar']],
            ['spreadsheet' => UploadedFile::fake()->create('dummy.doc', 3000)],
            ['spreadsheet' => UploadedFile::fake()->create('dummy.txt', 3000)],
            ['spreadsheet' => UploadedFile::fake()->create('dummy.csv', 3000)],
        ];

        $user = factory(User::class)->create()->makeSuperAdmin();

        Passport::actingAs($user);

        foreach ($invalidFieldTypes as $data) {
            $response = $this->json('POST', "/core/v1/organisations/import", $data);
            $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $organisations = factory(Organisation::class, 2)->states('web', 'email', 'phone')->make();

        $this->createOrganisationSpreadsheets($organisations);

        $response = $this->json('POST', "/core/v1/organisations/import", ['spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xls')))]);
        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJson([
            'data' => [
                'imported_row_count' => 2,
            ],
        ]);

        $organisations = factory(Organisation::class, 2)->states('web', 'email', 'phone')->make();

        $this->createOrganisationSpreadsheets($organisations);

        $response = $this->json('POST', "/core/v1/organisations/import", ['spreadsheet' => 'data:application/octet-stream;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xls')))]);
        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJson([
            'data' => [
                'imported_row_count' => 2,
            ],
        ]);

        $organisations = factory(Organisation::class, 2)->states('web', 'email', 'phone')->make();

        $this->createOrganisationSpreadsheets($organisations);

        $response = $this->json('POST', "/core/v1/organisations/import", ['spreadsheet' => 'data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xlsx')))]);
        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJson([
            'data' => [
                'imported_row_count' => 2,
            ],
        ]);
    }

    public function test_validate_file_import_fields()
    {
        Storage::fake('local');

        $user = factory(User::class)->create()->makeSuperAdmin();

        Passport::actingAs($user);

        $response = $this->json('POST', "/core/v1/organisations/import", ['spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(base_path('tests/assets/organisations_import_2_bad.xls')))]);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJson([
            'data' => [
                'errors' => [
                    'spreadsheet' => [
                        [
                            'row' => [],
                            'errors' => [
                                'name' => [],
                                'description' => [],
                                'url' => [],
                                'email' => [],
                            ],
                        ],
                        [
                            'row' => [],
                            'errors' => [
                                'email' => [],
                                'phone' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $response = $this->json('POST', "/core/v1/organisations/import", ['spreadsheet' => 'data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,' . base64_encode(file_get_contents(base_path('tests/assets/organisations_import_2_bad.xlsx')))]);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJson([
            'data' => [
                'errors' => [
                    'spreadsheet' => [
                        [
                            'row' => [],
                            'errors' => [
                                'name' => [],
                                'description' => [],
                                'url' => [],
                                'email' => [],
                            ],
                        ],
                        [
                            'row' => [],
                            'errors' => [
                                'email' => [],
                                'phone' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function test_organisations_file_import_100rows()
    {
        Storage::fake('local');

        $user = factory(User::class)->create()->makeSuperAdmin();

        Passport::actingAs($user);

        $organisations = factory(Organisation::class, 100)->states('web', 'email', 'phone')->make();

        $this->createOrganisationSpreadsheets($organisations);

        $response = $this->json('POST', "/core/v1/organisations/import", ['spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xls')))]);
        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJson([
            'data' => [
                'imported_row_count' => 100,
            ],
        ]);

        $organisations = factory(Organisation::class, 100)->states('web', 'email', 'phone')->make();

        $this->createOrganisationSpreadsheets($organisations);

        $response = $this->json('POST', "/core/v1/organisations/import", ['spreadsheet' => 'data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xlsx')))]);
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
    public function test_organisations_file_import_5krows()
    {
        Storage::fake('local');

        $user = factory(User::class)->create()->makeSuperAdmin();

        Passport::actingAs($user);

        $organisations = factory(Organisation::class, 5000)->states('web', 'email', 'phone')->make();

        $this->createOrganisationSpreadsheets($organisations);

        $response = $this->json('POST', "/core/v1/organisations/import", ['spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xls')))]);
        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJson([
            'data' => [
                'imported_row_count' => 5000,
            ],
        ]);

        $organisations = factory(Organisation::class, 5000)->states('web', 'email', 'phone')->make();

        $this->createOrganisationSpreadsheets($organisations);

        $response = $this->json('POST', "/core/v1/organisations/import", ['spreadsheet' => 'data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xls')))]);
        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJson([
            'data' => [
                'imported_row_count' => 5000,
            ],
        ]);
    }

    public function test_organisation_admins_created_on_import()
    {
        Storage::fake('local');

        $user = factory(User::class)->create(['first_name' => 'Super Admin'])->makeSuperAdmin();

        $admin1 = factory(User::class)->create(['first_name' => 'Global Admin 1'])->makeGlobalAdmin();
        $admin2 = factory(User::class)->create(['first_name' => 'Global Admin 2'])->makeGlobalAdmin();
        $admin3 = factory(User::class)->create(['first_name' => 'Global Admin 3'])->makeGlobalAdmin();

        Passport::actingAs($user);

        $organisations = factory(Organisation::class, 10)->states('web', 'email', 'phone')->make();

        $this->createOrganisationSpreadsheets($organisations);

        $response = $this->json('POST', "/core/v1/organisations/import", ['spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xls')))]);
        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJson([
            'data' => [
                'imported_row_count' => 10,
            ],
        ]);

        $organisationIds = \DB::table('organisations')->latest()->pluck('id');

        $this->assertDatabaseHas('user_roles', [
            'user_id' => $admin1->id,
            'organisation_id' => $organisationIds[0],
            'role_id' => Role::organisationAdmin()->id,
        ]);

        $this->assertDatabaseHas('user_roles', [
            'user_id' => $admin2->id,
            'organisation_id' => $organisationIds[0],
            'role_id' => Role::organisationAdmin()->id,
        ]);

        $this->assertDatabaseHas('user_roles', [
            'user_id' => $admin3->id,
            'organisation_id' => $organisationIds[0],
            'role_id' => Role::organisationAdmin()->id,
        ]);

        $organisationAdminCount = \DB::select('select count(*) as total from user_roles where role_id = ?', [Role::organisationAdmin()->id]);

        $this->assertEquals(40, $organisationAdminCount[0]->total);
    }

    /**
     * @test
     */
    public function duplicate_import_organisations_are_detected()
    {
        Storage::fake('local');

        $user = factory(User::class)->create()->makeSuperAdmin();

        Passport::actingAs($user);

        $organisation1 = factory(Organisation::class)->states('web', 'email', 'phone')->create(['name' => 'Current Organisation']);
        $organisation2 = factory(Organisation::class)->states('web', 'email', 'phone')->create(['name' => 'Current  Organisation']);
        $organisation3 = factory(Organisation::class)->states('web', 'email', 'phone')->create(['name' => 'Current "Organisation"']);
        $organisation4 = factory(Organisation::class)->states('web', 'email', 'phone')->create(['name' => 'Current.Organisation']);
        $organisation5 = factory(Organisation::class)->states('web', 'email', 'phone')->create(['name' => 'Current, Organisation']);
        $organisation6 = factory(Organisation::class)->states('web', 'email', 'phone')->create(['name' => 'Current-Organisation']);

        $organisations = collect([
            factory(Organisation::class)->states('web', 'email', 'phone')->make(['name' => 'Current Organisation']),
            factory(Organisation::class)->states('web', 'email', 'phone')->make(['name' => 'New Organisation']),
            factory(Organisation::class)->states('web', 'email', 'phone')->make(['name' => 'New Organisation']),
        ]);

        $this->createOrganisationSpreadsheets($organisations);

        $response = $this->json('POST', "/core/v1/organisations/import", [
            'spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xls'))),
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $headers = [
            'name',
            'description',
            'url',
            'email',
            'phone',
        ];
        $headersWithId = array_merge($headers, ['id']);

        $response->assertJsonFragment([
            collect($organisation1->getAttributes())->only($headersWithId)->all(),
        ]);
        $response->assertJsonFragment([
            'row' => collect($organisations->get(0)->getAttributes())->only($headers)->put('index', 2)->all(),
        ]);
        $response->assertJsonFragment([
            'email' => $organisations->get(2)->email,
        ]);
        $response->assertJsonFragment([
            'row' => collect($organisations->get(1)->getAttributes())->only($headers)->put('index', 3)->all(),
        ]);
        $response->assertJsonStructure([
            'data' => [
                'duplicates' => [
                    [
                        'row',
                        'originals' => [
                            $headersWithId,
                        ],
                    ],
                ],
                'imported_row_count',
            ],
        ]);
        $response->assertJsonFragment(collect($organisation1->getAttributes())->only($headersWithId)->all());
        $response->assertJsonFragment(collect($organisation2->getAttributes())->only($headersWithId)->all());
        $response->assertJsonFragment(collect($organisation3->getAttributes())->only($headersWithId)->all());
        $response->assertJsonFragment(collect($organisation4->getAttributes())->only($headersWithId)->all());
        $response->assertJsonFragment(collect($organisation5->getAttributes())->only($headersWithId)->all());
        $response->assertJsonFragment(collect($organisation6->getAttributes())->only($headersWithId)->all());
    }

    /**
     * @test
     */
    public function possible_duplicate_import_organisations_can_be_ignored()
    {
        Storage::fake('local');

        $user = factory(User::class)->create()->makeSuperAdmin();

        Passport::actingAs($user);

        $organisation1 = factory(Organisation::class)->states('web', 'email', 'phone')->create(['name' => 'Current Organisation']);
        $organisation2 = factory(Organisation::class)->states('web', 'email', 'phone')->create(['name' => 'Current  Organisation']);
        $organisation3 = factory(Organisation::class)->states('web', 'email', 'phone')->create(['name' => 'Current "Organisation"']);
        $organisation4 = factory(Organisation::class)->states('web', 'email', 'phone')->create(['name' => 'Current.Organisation']);
        $organisation5 = factory(Organisation::class)->states('web', 'email', 'phone')->create(['name' => 'Current, Organisation']);
        $organisation6 = factory(Organisation::class)->states('web', 'email', 'phone')->create(['name' => 'Current-Organisation']);
        $organisations = collect([
            factory(Organisation::class)->states('web', 'email', 'phone')->make(['name' => 'Current Organisation']),
            factory(Organisation::class)->states('web', 'email', 'phone')->make(['name' => 'New Organisation']),
        ]);

        $this->createOrganisationSpreadsheets($organisations);

        $response = $this->json('POST', "/core/v1/organisations/import", [
            'spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xls'))),
            'ignore_duplicates' => [
                $organisation1->id,
                $organisation2->id,
                $organisation3->id,
                $organisation4->id,
                $organisation5->id,
                $organisation6->id,
            ],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas('organisations', [
            'email' => $organisation1->email,
        ]);
        $this->assertDatabaseHas('organisations', [
            'email' => $organisation2->email,
        ]);
        $this->assertDatabaseHas('organisations', [
            'email' => $organisation3->email,
        ]);
        $this->assertDatabaseHas('organisations', [
            'email' => $organisation4->email,
        ]);
        $this->assertDatabaseHas('organisations', [
            'email' => $organisation5->email,
        ]);
        $this->assertDatabaseHas('organisations', [
            'email' => $organisation6->email,
        ]);
    }

    public function test_duplicate_rows_in_import_are_detected()
    {
        Storage::fake('local');

        $user = factory(User::class)->create()->makeSuperAdmin();

        Passport::actingAs($user);

        $organisation = factory(Organisation::class)->states('web', 'email', 'phone')->create([
            'name' => 'Current Organisation',
            'description' => 'Original Organisation',
        ]);

        $organisations = collect([
            factory(Organisation::class)->states('web', 'email', 'phone')->make([
                'name' => 'Current Organisation',
                'description' => 'Import Organisation 1',
            ]),
            factory(Organisation::class)->states('web', 'email', 'phone')->make([
                'name' => 'Current Organisation',
                'description' => 'Import Organisation 2',
            ]),
        ]);

        $this->createOrganisationSpreadsheets($organisations);

        $response = $this->json('POST', "/core/v1/organisations/import", [
            'spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xls'))),
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $headers = [
            'name',
            'description',
            'url',
            'email',
            'phone',
        ];

        $response->assertJson([
            'data' => [
                'imported_row_count' => 0,
            ],
        ]);
        $response->assertJsonFragment([
            'row' => collect($organisations->get(0)->getAttributes())->only($headers)->put('index', 2)->all(),
        ]);
        $response->assertJsonCount(2, 'data.duplicates.*.originals.*');
        $response->assertJsonFragment(collect($organisations->get(1)->getAttributes())->only($headers)->put('id', null)->all());
        $response->assertJsonFragment(collect($organisation->getAttributes())->only(array_merge($headers, ['id']))->all());
    }

    public function test_filter_organisations_by_is_admin()
    {
        $organisations = factory(Organisation::class, 5)->create();
        $service = factory(Service::class)->create([
            'organisation_id' => $organisations->get(0)->id,
        ]);

        $superAdmin = factory(User::class)->create()->makeSuperAdmin();
        $globalAdmin = factory(User::class)->create()->makeGlobalAdmin();
        $organisationAdmin = factory(User::class)->create()
            ->makeOrganisationAdmin($organisations->get(0))
            ->makeOrganisationAdmin($organisations->get(1));
        $serviceAdmin = factory(User::class)->create()->makeServiceAdmin($service);

        Passport::actingAs($serviceAdmin);

        $response = $this->json('GET', '/core/v1/organisations?filter[is_admin]=true');
        $data = $this->getResponseContent($response);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(0, 'data');

        Passport::actingAs($organisationAdmin);

        $response = $this->json('GET', '/core/v1/organisations?filter[is_admin]=true');
        $data = $this->getResponseContent($response);

        $response->assertStatus(Response::HTTP_OK);

        $response->assertJsonFragment([
            'id' => $organisations->get(0)->id,
        ]);
        $response->assertJsonFragment([
            'id' => $organisations->get(1)->id,
        ]);

        Passport::actingAs($globalAdmin);

        $response = $this->json('GET', '/core/v1/organisations?filter[is_admin]=true');
        $data = $this->getResponseContent($response);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(5, 'data');

        Passport::actingAs($superAdmin);

        $response = $this->json('GET', '/core/v1/organisations?filter[is_admin]=true');
        $data = $this->getResponseContent($response);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(5, 'data');
    }
}
