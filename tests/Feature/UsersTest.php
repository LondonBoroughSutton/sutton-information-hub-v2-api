<?php

namespace Tests\Feature;

use App\Events\EndpointHit;
use App\Models\Audit;
use App\Models\LocalAuthority;
use App\Models\Location;
use App\Models\Organisation;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\TestCase;

class UsersTest extends TestCase
{
    /*
     * ==================================================
     * List all the users.
     * ==================================================
     */

    public function test_guest_cannot_list_them()
    {
        $response = $this->json('GET', '/core/v1/users');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_can_list_them()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeServiceWorker(factory(User::class)->create(), $service);
        Passport::actingAs($user);

        $response = $this->json('GET', '/core/v1/users', ['include' => 'user-roles']);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'roles' => [
                [
                    'role' => Role::NAME_SERVICE_WORKER,
                    'service_id' => $service->id,
                ],
            ],
        ]);
    }

    public function test_audit_created_when_listed()
    {
        $this->fakeEvents();

        $service = factory(Service::class)->create();
        $user = $this->makeServiceWorker(factory(User::class)->create(), $service);
        Passport::actingAs($user);

        $this->json('GET', '/core/v1/users');

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getUser()->id === $user->id);
        });
    }

    public function test_service_worker_can_filter_by_highest_role_name()
    {
        $service = factory(Service::class)->create();
        $serviceAdmin = $this->makeServiceAdmin(factory(User::class)->create(), $service);
        $user = $this->makeServiceWorker(factory(User::class)->create(), $service);
        Passport::actingAs($user);

        $serviceAdminRoleName = Role::serviceAdmin()->name;

        $response = $this->json('GET', "/core/v1/users?filter[highest_role]={$serviceAdminRoleName}");

        $response->assertStatus(Response::HTTP_OK);
        $data = $this->getResponseContent($response);

        $this->assertEquals(1, count($data['data']));
        $this->assertEquals($serviceAdmin->id, $data['data'][0]['id']);
    }

    public function test_service_worker_can_sort_by_highest_role()
    {
        $service = factory(Service::class)->create();
        $serviceAdmin = $this->makeServiceAdmin(factory(User::class)->create(), $service);
        $serviceWorker = $this->makeServiceWorker(factory(User::class)->create(), $service);
        Passport::actingAs($serviceWorker);

        $response = $this->json('GET', '/core/v1/users?sort=-highest_role');

        $response->assertStatus(Response::HTTP_OK);
        $data = $this->getResponseContent($response);

        $this->assertEquals($serviceAdmin->id, $data['data'][1]['id']);
        $this->assertEquals($serviceWorker->id, $data['data'][0]['id']);
    }

    public function test_service_worker_can_sort_by_at_organisation()
    {
        $organisation = factory(Organisation::class)->create();
        $organisationAdmin = $this->makeOrganisationAdmin(factory(User::class)->create(), $organisation);
        $user = $user = $this->makeServiceWorker(factory(User::class)->create(), factory(Service::class)->create());
        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/users?filter[at_organisation]={$organisation->id}");

        $response->assertStatus(Response::HTTP_OK);
        $data = $this->getResponseContent($response);

        $this->assertEquals(1, count($data['data']));
        $this->assertEquals($organisationAdmin->id, $data['data'][0]['id']);
    }

    public function test_service_worker_can_sort_by_at_service()
    {
        $service = factory(Service::class)->create();
        $serviceAdmin = $this->makeServiceAdmin(factory(User::class)->create(), $service);
        $user = $user = $this->makeServiceWorker(factory(User::class)->create(), factory(Service::class)->create());
        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/users?filter[at_service]={$service->id}");

        $response->assertStatus(Response::HTTP_OK);
        $data = $this->getResponseContent($response);

        $this->assertEquals(1, count($data['data']));
        $this->assertEquals($serviceAdmin->id, $data['data'][0]['id']);
    }

    public function test_service_worker_can_sort_by_at_organisation_and_includes_service_workers()
    {
        $service = factory(Service::class)->create();
        $serviceWorker = $this->makeServiceWorker(factory(User::class)->create(), $service);
        $user = $user = $this->makeServiceWorker(factory(User::class)->create(), factory(Service::class)->create());
        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/users?filter[at_organisation]={$service->organisation_id}");

        $response->assertStatus(Response::HTTP_OK);
        $data = $this->getResponseContent($response);

        $this->assertEquals(1, count($data['data']));
        $this->assertEquals($serviceWorker->id, $data['data'][0]['id']);
    }

    public function test_service_worker_can_sort_by_at_organisation_and_excludes_global_admins()
    {
        $organisation = factory(Organisation::class)->create();
        $organisationAdmin = $this->makeOrganisationAdmin(factory(User::class)->create(), $organisation);
        // This user shouldn't show up in the results.
        $this->makeGlobalAdmin(factory(User::class)->create());
        $user = $this->makeServiceWorker(factory(User::class)->create(), factory(Service::class)->create());
        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/users?filter[at_organisation]={$organisation->id}");

        $response->assertStatus(Response::HTTP_OK);
        $data = $this->getResponseContent($response);

        $this->assertEquals(1, count($data['data']));
        $this->assertEquals($organisationAdmin->id, $data['data'][0]['id']);
    }

    /*
     * ==================================================
     * Create a user.
     * ==================================================
     */

    /*
     * Guest Invoked.
     */
    public function test_guest_cannot_create_one()
    {
        $response = $this->json('POST', '/core/v1/users', $this->getCreateUserPayload([
            ['role' => Role::NAME_SUPER_ADMIN],
        ]));

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /*
     * Service Worker Invoked.
     */
    public function test_service_worker_cannot_create_one()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeServiceWorker(factory(User::class)->create(), $service);
        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/users', $this->getCreateUserPayload([
            ['role' => Role::NAME_SUPER_ADMIN],
        ]));

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /*
     * Service Admin Invoked.
     */
    public function test_service_admin_cannot_create_service_worker_for_another_service()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeServiceAdmin(factory(User::class)->create(), $service);
        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/users', $this->getCreateUserPayload([
            [
                'role' => Role::NAME_SERVICE_WORKER,
                'service_id' => factory(Service::class)->create()->id,
            ],
        ]));

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_service_admin_can_create_service_worker_for_their_service()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeServiceAdmin(factory(User::class)->create(), $service);
        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/users', $this->getCreateUserPayload([
            [
                'role' => Role::NAME_SERVICE_WORKER,
                'service_id' => $service->id,
            ],
        ]));

        $response->assertStatus(Response::HTTP_CREATED);
        $createdUserId = json_decode($response->getContent(), true)['data']['id'];
        $createdUser = User::findOrFail($createdUserId);
        $this->assertTrue($createdUser->isServiceWorker($service));
        $this->assertEquals(1, $createdUser->roles()->count());
    }

    public function test_service_admin_cannot_create_service_admin_for_another_service()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeServiceAdmin(factory(User::class)->create(), $service);
        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/users', $this->getCreateUserPayload([
            [
                'role' => Role::NAME_SERVICE_ADMIN,
                'service_id' => factory(Service::class)->create()->id,
            ],
        ]));

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_service_admin_can_create_service_admin_for_their_service()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeServiceAdmin(factory(User::class)->create(), $service);
        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/users', $this->getCreateUserPayload([
            [
                'role' => Role::NAME_SERVICE_ADMIN,
                'service_id' => $service->id,
            ],
        ]));

        $response->assertStatus(Response::HTTP_CREATED);
        $createdUserId = json_decode($response->getContent(), true)['data']['id'];
        $createdUser = User::findOrFail($createdUserId);
        $this->assertTrue($createdUser->isServiceWorker($service));
        $this->assertTrue($createdUser->isServiceAdmin($service));
        $this->assertEquals(1, $createdUser->roles()->count());
    }

    public function test_service_admin_cannot_create_organisation_admin()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeServiceAdmin(factory(User::class)->create(), $service);
        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/users', $this->getCreateUserPayload([
            [
                'role' => Role::NAME_ORGANISATION_ADMIN,
                'organisation_id' => $service->organisation->id,
            ],
        ]));

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /*
     * Organisation Admin Invoked.
     */
    public function test_organisation_admin_cannot_create_service_worker_for_another_service()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeOrganisationAdmin(factory(User::class)->create(), $service->organisation);
        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/users', $this->getCreateUserPayload([
            [
                'role' => Role::NAME_SERVICE_WORKER,
                'service_id' => factory(Service::class)->create()->id,
            ],
        ]));

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_organisation_admin_can_create_service_worker_for_their_service()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeOrganisationAdmin(factory(User::class)->create(), $service->organisation);
        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/users', $this->getCreateUserPayload([
            [
                'role' => Role::NAME_SERVICE_WORKER,
                'service_id' => $service->id,
            ],
        ]));

        $response->assertStatus(Response::HTTP_CREATED);
        $createdUserId = json_decode($response->getContent(), true)['data']['id'];
        $createdUser = User::findOrFail($createdUserId);
        $this->assertTrue($createdUser->isServiceWorker($service));
        $this->assertEquals(1, $createdUser->roles()->count());
    }

    public function test_organisation_admin_cannot_create_service_admin_for_another_service()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeOrganisationAdmin(factory(User::class)->create(), $service->organisation);
        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/users', $this->getCreateUserPayload([
            [
                'role' => Role::NAME_SERVICE_ADMIN,
                'service_id' => factory(Service::class)->create()->id,
            ],
        ]));

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_organisation_admin_can_create_service_admin_for_their_service()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeOrganisationAdmin(factory(User::class)->create(), $service->organisation);
        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/users', $this->getCreateUserPayload([
            [
                'role' => Role::NAME_SERVICE_ADMIN,
                'service_id' => $service->id,
            ],
        ]));

        $response->assertStatus(Response::HTTP_CREATED);
        $createdUserId = json_decode($response->getContent(), true)['data']['id'];
        $createdUser = User::findOrFail($createdUserId);
        $this->assertTrue($createdUser->isServiceWorker($service));
        $this->assertTrue($createdUser->isServiceAdmin($service));
        $this->assertEquals(1, $createdUser->roles()->count());
    }

    public function test_organisation_admin_cannot_create_organisation_admin_for_another_organisation()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeOrganisationAdmin(factory(User::class)->create(), $service->organisation);
        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/users', $this->getCreateUserPayload([
            [
                'role' => Role::NAME_ORGANISATION_ADMIN,
                'organisation_id' => factory(Organisation::class)->create()->id,
            ],
        ]));

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_organisation_admin_can_create_organisation_admin_for_their_organisation()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeOrganisationAdmin(factory(User::class)->create(), $service->organisation);
        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/users', $this->getCreateUserPayload([
            [
                'role' => Role::NAME_ORGANISATION_ADMIN,
                'organisation_id' => $service->organisation->id,
            ],
        ]));

        $response->assertStatus(Response::HTTP_CREATED);
        $createdUserId = json_decode($response->getContent(), true)['data']['id'];
        $createdUser = User::findOrFail($createdUserId);
        $this->assertTrue($createdUser->isServiceWorker($service));
        $this->assertTrue($createdUser->isServiceAdmin($service));
        $this->assertTrue($createdUser->isOrganisationAdmin($service->organisation));
        $this->assertEquals(1, $createdUser->roles()->count());
    }

    public function test_organisation_admin_cannot_create_global_admin()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeOrganisationAdmin(factory(User::class)->create(), $service->organisation);
        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/users', $this->getCreateUserPayload([
            ['role' => Role::NAME_GLOBAL_ADMIN],
        ]));

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Local Admin Invoked
     */

    public function test_local_admin_cannot_create_user_roles()
    {
        $invoker = $this->makeLocalAdmin(factory(User::class)->create());
        Passport::actingAs($invoker);

        $service = factory(Service::class)->create();

        $response = $this->json('POST', "/core/v1/users", $this->getCreateUserPayload([
            [
                'role' => Role::NAME_SERVICE_WORKER,
                'service_id' => $service->id,
            ],
        ]));
        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $response = $this->json('POST', "/core/v1/users", $this->getCreateUserPayload([
            [
                'role' => Role::NAME_SERVICE_ADMIN,
                'service_id' => $service->id,
            ],
        ]));
        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $response = $this->json('POST', "/core/v1/users", $this->getCreateUserPayload([
            [
                'role' => Role::NAME_ORGANISATION_ADMIN,
                'organisation_id' => $service->organisation->id,
            ],
        ]));
        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $response = $this->json('POST', "/core/v1/users", $this->getCreateUserPayload([
            [
                'role' => Role::NAME_GLOBAL_ADMIN,
            ],
        ]));
        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $response = $this->json('POST', "/core/v1/users", $this->getCreateUserPayload([
            [
                'role' => Role::NAME_SUPER_ADMIN,
            ],
        ]));
        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /*
     * Global Admin Invoked.
     */
    public function test_global_admin_can_create_service_worker()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeGlobalAdmin(factory(User::class)->create());
        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/users', $this->getCreateUserPayload([
            [
                'role' => Role::NAME_SERVICE_WORKER,
                'service_id' => $service->id,
            ],
        ]));

        $response->assertStatus(Response::HTTP_CREATED);
        $createdUserId = json_decode($response->getContent(), true)['data']['id'];
        $createdUser = User::findOrFail($createdUserId);
        $this->assertTrue($createdUser->isServiceWorker($service));
        $this->assertEquals(1, $createdUser->roles()->count());
    }

    public function test_global_admin_can_create_service_admin()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeGlobalAdmin(factory(User::class)->create());
        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/users', $this->getCreateUserPayload([
            [
                'role' => Role::NAME_SERVICE_ADMIN,
                'service_id' => $service->id,
            ],
        ]));

        $response->assertStatus(Response::HTTP_CREATED);
        $createdUserId = json_decode($response->getContent(), true)['data']['id'];
        $createdUser = User::findOrFail($createdUserId);
        $this->assertTrue($createdUser->isServiceWorker($service));
        $this->assertTrue($createdUser->isServiceAdmin($service));
        $this->assertEquals(1, $createdUser->roles()->count());
    }

    public function test_global_admin_can_create_organisation_admin()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeGlobalAdmin(factory(User::class)->create());
        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/users', $this->getCreateUserPayload([
            [
                'role' => Role::NAME_ORGANISATION_ADMIN,
                'organisation_id' => $service->organisation->id,
            ],
        ]));

        $response->assertStatus(Response::HTTP_CREATED);
        $createdUserId = json_decode($response->getContent(), true)['data']['id'];
        $createdUser = User::findOrFail($createdUserId);
        $this->assertTrue($createdUser->isServiceWorker($service));
        $this->assertTrue($createdUser->isServiceAdmin($service));
        $this->assertTrue($createdUser->isOrganisationAdmin($service->organisation));
        $this->assertEquals(1, $createdUser->roles()->count());
    }

    public function test_global_admin_can_create_local_admin()
    {
        $service = factory(Service::class)->create();
        $serviceAdmin = $this->makeServiceAdmin(factory(User::class)->create(), $service);
        $organisationAdmin = $this->makeOrganisationAdmin(factory(User::class)->create(), $service->organisation);
        $localAdmin = $this->makeLocalAdmin(factory(User::class)->create());
        $globalAdmin = $this->makeGlobalAdmin(factory(User::class)->create());

        $payload = $this->getCreateUserPayload([
            [
                'role' => Role::NAME_ORGANISATION_ADMIN,
                'organisation_id' => $service->organisation->id,
            ],
            ['role' => Role::NAME_LOCAL_ADMIN],
        ]);

        Passport::actingAs($serviceAdmin);
        $response = $this->json('POST', '/core/v1/users', $payload);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        Passport::actingAs($organisationAdmin);
        $response = $this->json('POST', '/core/v1/users', $payload);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        Passport::actingAs($localAdmin);
        $response = $this->json('POST', '/core/v1/users', $payload);
        $response->assertStatus(Response::HTTP_FORBIDDEN);

        Passport::actingAs($globalAdmin);
        $response = $this->json('POST', '/core/v1/users', $payload);
        $response->assertStatus(Response::HTTP_CREATED);

        $createdUserId = $response->json('data.id');
        $createdUser = User::findOrFail($createdUserId);
        $this->assertTrue($createdUser->isOrganisationAdmin($service->organisation));
        $this->assertTrue($createdUser->isLocalAdmin());
    }

    public function test_global_admin_can_create_global_admin()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeGlobalAdmin(factory(User::class)->create());
        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/users', $this->getCreateUserPayload([
            ['role' => Role::NAME_GLOBAL_ADMIN],
        ]));

        $response->assertStatus(Response::HTTP_CREATED);
        $createdUserId = json_decode($response->getContent(), true)['data']['id'];
        $createdUser = User::findOrFail($createdUserId);
        $this->assertTrue($createdUser->isServiceWorker($service));
        $this->assertTrue($createdUser->isServiceAdmin($service));
        $this->assertTrue($createdUser->isOrganisationAdmin($service->organisation));
        $this->assertTrue($createdUser->isGlobalAdmin());
        $this->assertEquals(1, $createdUser->roles()->count());
    }

    public function test_global_admin_cannot_create_super_admin()
    {
        $user = $this->makeGlobalAdmin(factory(User::class)->create());
        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/users', $this->getCreateUserPayload([
            ['role' => Role::NAME_SUPER_ADMIN],
        ]));

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /*
     * Super Admin Invoked.
     */

    public function test_super_admin_can_create_service_worker()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeSuperAdmin(factory(User::class)->create());
        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/users', $this->getCreateUserPayload([
            [
                'role' => Role::NAME_SERVICE_WORKER,
                'service_id' => $service->id,
            ],
        ]));

        $response->assertStatus(Response::HTTP_CREATED);
        $createdUserId = json_decode($response->getContent(), true)['data']['id'];
        $createdUser = User::findOrFail($createdUserId);
        $this->assertTrue($createdUser->isServiceWorker($service));
        $this->assertEquals(1, $createdUser->roles()->count());
    }

    public function test_super_admin_can_create_service_admin()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeSuperAdmin(factory(User::class)->create());
        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/users', $this->getCreateUserPayload([
            [
                'role' => Role::NAME_SERVICE_ADMIN,
                'service_id' => $service->id,
            ],
        ]));

        $response->assertStatus(Response::HTTP_CREATED);
        $createdUserId = json_decode($response->getContent(), true)['data']['id'];
        $createdUser = User::findOrFail($createdUserId);
        $this->assertTrue($createdUser->isServiceWorker($service));
        $this->assertTrue($createdUser->isServiceAdmin($service));
        $this->assertEquals(1, $createdUser->roles()->count());
    }

    public function test_super_admin_can_create_organisation_admin()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeSuperAdmin(factory(User::class)->create());
        Passport::actingAs($user);

        $payload = $this->getCreateUserPayload([
            [
                'role' => Role::NAME_ORGANISATION_ADMIN,
                'organisation_id' => $service->organisation->id,
            ],
        ]);
        $payload['employer_name'] = $service->organisation->name;
        $response = $this->json('POST', '/core/v1/users', $payload);

        $response->assertStatus(Response::HTTP_CREATED);
        $createdUserId = json_decode($response->getContent(), true)['data']['id'];
        $createdUser = User::findOrFail($createdUserId);
        $this->assertTrue($createdUser->isServiceWorker($service));
        $this->assertTrue($createdUser->isServiceAdmin($service));
        $this->assertTrue($createdUser->isOrganisationAdmin($service->organisation));
        $this->assertEquals(1, $createdUser->roles()->count());
        $this->assertEquals($service->organisation->name, $createdUser->employer_name);
    }

    public function test_only_super_admin_can_create_user_with_employer_name()
    {
        $service = factory(Service::class)->create();
        $serviceAdmin = $this->makeServiceAdmin(factory(User::class)->create(), $service);
        $orgAdmin = $this->makeOrganisationAdmin(factory(User::class)->create(), $service->organisation);
        $globalAdmin = $this->makeGlobalAdmin(factory(User::class)->create());
        $superAdmin = $this->makeSuperAdmin(factory(User::class)->create());

        $payload = $this->getCreateUserPayload([
            ['role' => Role::NAME_GLOBAL_ADMIN],
        ]);
        $payload['employer_name'] = $service->organisation->name;

        Passport::actingAs($serviceAdmin);
        $response = $this->json('POST', '/core/v1/users', $payload);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        Passport::actingAs($orgAdmin);
        $response = $this->json('POST', '/core/v1/users', $payload);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        Passport::actingAs($globalAdmin);
        $response = $this->json('POST', '/core/v1/users', $payload);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        Passport::actingAs($superAdmin);
        $response = $this->json('POST', '/core/v1/users', $payload);
        $response->assertStatus(Response::HTTP_CREATED);
    }

    public function test_only_super_admin_can_create_user_with_location_id()
    {
        $service = factory(Service::class)->create();
        $location = factory(Location::class)->create();
        $serviceAdmin = $this->makeServiceAdmin(factory(User::class)->create(), $service);
        $orgAdmin = $this->makeOrganisationAdmin(factory(User::class)->create(), $service->organisation);
        $globalAdmin = $this->makeGlobalAdmin(factory(User::class)->create());
        $superAdmin = $this->makeSuperAdmin(factory(User::class)->create());

        $payload = $this->getCreateUserPayload([
            ['role' => Role::NAME_GLOBAL_ADMIN],
        ]);
        $payload['location_id'] = $location->id;

        Passport::actingAs($serviceAdmin);
        $response = $this->json('POST', '/core/v1/users', $payload);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        Passport::actingAs($orgAdmin);
        $response = $this->json('POST', '/core/v1/users', $payload);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        Passport::actingAs($globalAdmin);
        $response = $this->json('POST', '/core/v1/users', $payload);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        Passport::actingAs($superAdmin);
        $response = $this->json('POST', '/core/v1/users', $payload);
        $response->assertStatus(Response::HTTP_CREATED);
    }

    public function test_only_super_admin_can_create_user_with_local_authority_id()
    {
        $service = factory(Service::class)->create();
        $localAuthority = factory(LocalAuthority::class)->create();
        $serviceAdmin = $this->makeServiceAdmin(factory(User::class)->create(), $service);
        $orgAdmin = $this->makeOrganisationAdmin(factory(User::class)->create(), $service->organisation);
        $globalAdmin = $this->makeGlobalAdmin(factory(User::class)->create());
        $superAdmin = $this->makeSuperAdmin(factory(User::class)->create());

        $payload = $this->getCreateUserPayload([
            ['role' => Role::NAME_GLOBAL_ADMIN],
        ]);
        $payload['local_authority_id'] = $localAuthority->id;

        Passport::actingAs($serviceAdmin);
        $response = $this->json('POST', '/core/v1/users', $payload);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        Passport::actingAs($orgAdmin);
        $response = $this->json('POST', '/core/v1/users', $payload);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        Passport::actingAs($globalAdmin);
        $response = $this->json('POST', '/core/v1/users', $payload);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        Passport::actingAs($superAdmin);
        $response = $this->json('POST', '/core/v1/users', $payload);
        $response->assertStatus(Response::HTTP_CREATED);
    }

    public function test_super_admin_can_create_global_admin()
    {
        $service = factory(Service::class)->create();
        $location = factory(Location::class)->create();
        $localAuthority = factory(LocalAuthority::class)->create();
        $user = $this->makeSuperAdmin(factory(User::class)->create());
        Passport::actingAs($user);

        $payload = $this->getCreateUserPayload([
            ['role' => Role::NAME_GLOBAL_ADMIN],
        ]);
        $payload['employer_name'] = $service->organisation->name;
        $payload['location_id'] = $location->id;
        $payload['local_authority_id'] = $localAuthority->id;
        $response = $this->json('POST', '/core/v1/users', $payload);

        $response->assertStatus(Response::HTTP_CREATED);
        $createdUserId = json_decode($response->getContent(), true)['data']['id'];
        $createdUser = User::findOrFail($createdUserId);
        $this->assertTrue($createdUser->isServiceWorker($service));
        $this->assertTrue($createdUser->isServiceAdmin($service));
        $this->assertTrue($createdUser->isOrganisationAdmin($service->organisation));
        $this->assertTrue($createdUser->isGlobalAdmin());
        $this->assertEquals(1, $createdUser->roles()->count());
        $this->assertEquals($service->organisation->name, $createdUser->employer_name);

        $response->assertJsonFragment([
            'address' => [
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

        $response->assertJsonFragment([
            'local_authority' => [
                'id' => $localAuthority->id,
                'name' => $localAuthority->name,
                'alt_name' => $localAuthority->alt_name,
                'code' => $localAuthority->code,
                'region' => $localAuthority->region(),
                'created_at' => $localAuthority->created_at->format(CarbonImmutable::ISO8601),
                'updated_at' => $localAuthority->updated_at->format(CarbonImmutable::ISO8601),
            ],
        ]);
    }

    public function test_super_admin_can_create_super_admin()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeSuperAdmin(factory(User::class)->create());
        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/users', $this->getCreateUserPayload([
            ['role' => Role::NAME_SUPER_ADMIN],
        ]));

        $response->assertStatus(Response::HTTP_CREATED);
        $createdUserId = json_decode($response->getContent(), true)['data']['id'];
        $createdUser = User::findOrFail($createdUserId);
        $this->assertTrue($createdUser->isServiceWorker($service));
        $this->assertTrue($createdUser->isServiceAdmin($service));
        $this->assertTrue($createdUser->isOrganisationAdmin($service->organisation));
        $this->assertTrue($createdUser->isGlobalAdmin());
        $this->assertTrue($createdUser->isSuperAdmin());
        $this->assertEquals(1, $createdUser->roles()->count());
    }

    public function test_super_admin_can_create_super_admin_with_soft_deleted_users_email()
    {
        $user = $this->makeSuperAdmin(factory(User::class)->create());
        Passport::actingAs($user);

        $deletedUser = $this->makeSuperAdmin(factory(User::class)->create(['email' => 'test@example.com']));
        $deletedUser->delete();

        $response = $this->json('POST', '/core/v1/users', [
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => 'test@example.com',
            'phone' => random_uk_phone(),
            'password' => 'Pa$$w0rd',
            'roles' => [
                ['role' => Role::NAME_SUPER_ADMIN],
            ],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $this->assertDatabaseHas(table(User::class), [
            'id' => $deletedUser->id,
            'email' => 'test@example.com',
        ]);
        $this->assertDatabaseMissing(table(User::class), [
            'id' => $deletedUser->id,
            'email' => 'test@example.com',
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas(table(User::class), [
            'email' => 'test@example.com',
            'deleted_at' => null,
        ]);
    }

    /*
     * Audit.
     */

    public function test_audit_created_when_created()
    {
        $this->fakeEvents();

        $user = $this->makeSuperAdmin(factory(User::class)->create());
        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/users', $this->getCreateUserPayload([
            ['role' => Role::NAME_SUPER_ADMIN],
        ]));

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $response) {
            return ($event->getAction() === Audit::ACTION_CREATE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $this->getResponseContent($response)['data']['id']);
        });
    }

    /*
     * ==================================================
     * Get a specific user.
     * ==================================================
     */

    public function test_guest_cannot_view_one()
    {
        factory(Service::class)->create();
        $user = $this->makeSuperAdmin(factory(User::class)->create());

        $response = $this->json('GET', "/core/v1/users/{$user->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_can_view_one()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeServiceWorker(factory(User::class)->create(), $service);
        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/users/{$user->id}", ['include' => 'user-roles']);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'roles' => [
                [
                    'role' => Role::NAME_SERVICE_WORKER,
                    'service_id' => $service->id,
                ],
            ],
        ]);
    }

    public function test_audit_created_when_viewed()
    {
        $this->fakeEvents();

        $service = factory(Service::class)->create();
        $user = $this->makeServiceWorker(factory(User::class)->create(), $service);
        Passport::actingAs($user);

        $this->json('GET', "/core/v1/users/{$user->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $user->id);
        });
    }

    /*
     * ==================================================
     * Get the logged in user.
     * ==================================================
     */

    public function test_guest_cannot_view_logged_in_user()
    {
        factory(Service::class)->create();

        $response = $this->json('GET', '/core/v1/users/users');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_can_view_logged_in_user()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeServiceWorker(factory(User::class)->create(), $service);
        Passport::actingAs($user);

        $response = $this->json('GET', '/core/v1/users/user', ['include' => 'user-roles']);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'roles' => [
                [
                    'role' => Role::NAME_SERVICE_WORKER,
                    'service_id' => $service->id,
                ],
            ],
        ]);
    }

    public function test_audit_created_when_logged_in_user_viewed()
    {
        $this->fakeEvents();

        $service = factory(Service::class)->create();
        $user = $this->makeServiceWorker(factory(User::class)->create(), $service);
        Passport::actingAs($user);

        $this->json('GET', '/core/v1/users/user');

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $user->id);
        });
    }

    /*
     * ==================================================
     * Update a specific user.
     * ==================================================
     */

    /*
     * Guest Invoked.
     */
    public function test_guest_cannot_update_one()
    {
        factory(Service::class)->create();
        $user = $this->makeSuperAdmin(factory(User::class)->create());

        $response = $this->json('PUT', "/core/v1/users/{$user->id}", $this->getCreateUserPayload([
            ['role' => Role::NAME_SERVICE_ADMIN],
        ]));

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /*
     * Service Worker Invoked.
     */
    public function test_service_worker_cannot_update_one()
    {
        $service = factory(Service::class)->create();
        $invoker = $this->makeServiceWorker(factory(User::class)->create(), $service);
        Passport::actingAs($invoker);

        $user = $this->makeSuperAdmin(factory(User::class)->create());

        $response = $this->json('PUT', "/core/v1/users/{$user->id}", $this->getCreateUserPayload([
            ['role' => Role::NAME_SUPER_ADMIN],
        ]));

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_worker_can_update_their_own()
    {
        $service = factory(Service::class)->create();
        $invoker = $this->makeServiceWorker(factory(User::class)->create(), $service);
        Passport::actingAs($invoker);

        $response = $this->json('PUT', "/core/v1/users/{$invoker->id}", $this->getCreateUserPayload([
            [
                'role' => Role::NAME_SERVICE_WORKER,
                'service_id' => $service->id,
            ],
        ]));

        $response->assertStatus(Response::HTTP_OK);
    }

    /*
     * Service Admin Invoked.
     */
    public function test_service_admin_can_update_service_worker()
    {
        $invoker = $this->makeServiceAdmin(factory(User::class)->create(), factory(Service::class)->create());
        Passport::actingAs($invoker);

        $service = factory(Service::class)->create();
        $user = $this->makeServiceWorker(factory(User::class)->create(), $service);

        $response = $this->json('PUT', "/core/v1/users/{$user->id}", [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'password' => 'Pa$$w0rd',
            'roles' => [
                [
                    'role' => Role::NAME_SERVICE_WORKER,
                    'service_id' => $service->id,
                ],
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'roles' => [
                [
                    'role' => Role::NAME_SERVICE_WORKER,
                    'service_id' => $service->id,
                ],
            ],
        ]);
    }

    public function test_service_admin_can_update_service_admin()
    {
        $service = factory(Service::class)->create();
        $invoker = $this->makeServiceAdmin(factory(User::class)->create(), $service);
        Passport::actingAs($invoker);

        $subject = $this->makeServiceAdmin(factory(User::class)->create(), $service);

        $response = $this->json('PUT', "/core/v1/users/{$subject->id}", [
            'first_name' => $subject->first_name,
            'last_name' => $subject->last_name,
            'email' => $subject->email,
            'phone' => $subject->phone,
            'password' => 'Pa$$w0rd',
            'roles' => [
                [
                    'role' => Role::NAME_SERVICE_WORKER,
                    'service_id' => $service->id,
                ],
                [
                    'role' => Role::NAME_SERVICE_ADMIN,
                    'service_id' => $service->id,
                ],
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'first_name' => $subject->first_name,
            'last_name' => $subject->last_name,
            'email' => $subject->email,
            'phone' => $subject->phone,
        ]);
        $response->assertJsonCount(1, 'data.roles');
        $response->assertJsonFragment([
            'role' => Role::NAME_SERVICE_ADMIN,
            'service_id' => $service->id,
        ]);
    }

    public function test_service_admin_cannot_update_organisation_admin()
    {
        $service = factory(Service::class)->create();
        $invoker = $this->makeServiceAdmin(factory(User::class)->create(), $service);
        Passport::actingAs($invoker);

        $subject = $this->makeOrganisationAdmin(factory(User::class)->create(), $service->organisation);

        $response = $this->json('PUT', "/core/v1/users/{$subject->id}", [
            'first_name' => $subject->first_name,
            'last_name' => $subject->last_name,
            'email' => $subject->email,
            'phone' => $subject->phone,
            'password' => 'Pa$$w0rd',
            'roles' => [
                [
                    'role' => Role::NAME_SERVICE_WORKER,
                    'service_id' => $service->id,
                ],
                [
                    'role' => Role::NAME_SERVICE_ADMIN,
                    'service_id' => $service->id,
                ],
                [
                    'role' => Role::NAME_ORGANISATION_ADMIN,
                    'organisation_id' => $service->organisation->id,
                ],
            ],
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /*
     * Organisation Admin Invoked.
     */
    public function test_organisation_admin_can_update_service_worker()
    {
        $service = factory(Service::class)->create();
        $invoker = $this->makeOrganisationAdmin(factory(User::class)->create(), $service->organisation);
        Passport::actingAs($invoker);

        $user = $this->makeServiceWorker(factory(User::class)->create(), $service);

        $response = $this->json('PUT', "/core/v1/users/{$user->id}", [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'password' => 'Pa$$w0rd',
            'roles' => [
                [
                    'role' => Role::NAME_SERVICE_WORKER,
                    'service_id' => $service->id,
                ],
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'roles' => [
                [
                    'role' => Role::NAME_SERVICE_WORKER,
                    'service_id' => $service->id,
                ],
            ],
        ]);
    }

    public function test_organisation_admin_can_update_service_admin()
    {
        $service = factory(Service::class)->create();
        $invoker = $this->makeOrganisationAdmin(factory(User::class)->create(), $service->organisation);
        Passport::actingAs($invoker);

        $user = $this->makeServiceAdmin(factory(User::class)->create(), $service);

        $response = $this->json('PUT', "/core/v1/users/{$user->id}", [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'password' => 'Pa$$w0rd',
            'roles' => [
                [
                    'role' => Role::NAME_SERVICE_WORKER,
                    'service_id' => $service->id,
                ],
                [
                    'role' => Role::NAME_SERVICE_ADMIN,
                    'service_id' => $service->id,
                ],
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
        ]);
        $response->assertJsonCount(1, 'data.roles');
        $response->assertJsonFragment([
            'role' => Role::NAME_SERVICE_ADMIN,
            'service_id' => $service->id,
        ]);
    }

    public function test_organisation_admin_can_update_organisation_admin()
    {
        $service = factory(Service::class)->create();
        $invoker = $this->makeOrganisationAdmin(factory(User::class)->create(), $service->organisation);
        Passport::actingAs($invoker);

        $user = $this->makeOrganisationAdmin(factory(User::class)->create(), $service->organisation);

        $response = $this->json('PUT', "/core/v1/users/{$user->id}", [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'password' => 'Pa$$w0rd',
            'roles' => [
                [
                    'role' => Role::NAME_SERVICE_WORKER,
                    'service_id' => $service->id,
                ],
                [
                    'role' => Role::NAME_SERVICE_ADMIN,
                    'service_id' => $service->id,
                ],
                [
                    'role' => Role::NAME_ORGANISATION_ADMIN,
                    'organisation_id' => $service->organisation->id,
                ],
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
        ]);
        $response->assertJsonCount(1, 'data.roles');
        $response->assertJsonFragment([
            'role' => Role::NAME_ORGANISATION_ADMIN,
            'organisation_id' => $service->organisation->id,
        ]);
    }

    public function test_organisation_admin_cannot_update_global_admin()
    {
        $service = factory(Service::class)->create();
        $invoker = $this->makeOrganisationAdmin(factory(User::class)->create(), $service->organisation);
        Passport::actingAs($invoker);

        $user = $this->makeGlobalAdmin(factory(User::class)->create());

        $response = $this->json('PUT', "/core/v1/users/{$user->id}", [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'password' => 'Pa$$w0rd',
            'roles' => [
                [
                    'role' => Role::NAME_SERVICE_WORKER,
                    'service_id' => $service->id,
                ],
                [
                    'role' => Role::NAME_SERVICE_ADMIN,
                    'service_id' => $service->id,
                ],
                [
                    'role' => Role::NAME_ORGANISATION_ADMIN,
                    'organisation_id' => $service->organisation->id,
                ],
                [
                    'role' => Role::NAME_GLOBAL_ADMIN,
                ],
            ],
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * Local Admin Invoked
     */

    public function test_local_admin_cannot_update_user_roles()
    {
        $invoker = $this->makeLocalAdmin(factory(User::class)->create());
        Passport::actingAs($invoker);

        $service = factory(Service::class)->create();
        $user = factory(User::class)->create();

        $payload = [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'password' => 'Pa$$w0rd',
            'roles' => [
                [
                    'role' => Role::NAME_SERVICE_WORKER,
                    'service_id' => $service->id,
                ],
            ],
        ];

        $response = $this->json('PUT', "/core/v1/users/{$user->id}", $payload);
        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $payload['roles'] = [[
            'role' => Role::NAME_SERVICE_ADMIN,
            'service_id' => $service->id,
        ]];

        $response = $this->json('PUT', "/core/v1/users/{$user->id}", $payload);
        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $payload['roles'] = [[
            'role' => Role::NAME_ORGANISATION_ADMIN,
            'organisation_id' => $service->organisation->id,
        ]];

        $response = $this->json('PUT', "/core/v1/users/{$user->id}", $payload);
        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $payload['roles'] = [[
            'role' => Role::NAME_GLOBAL_ADMIN,
        ]];

        $response = $this->json('PUT', "/core/v1/users/{$user->id}", $payload);
        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $payload['roles'] = [[
            'role' => Role::NAME_SUPER_ADMIN,
        ]];

        $response = $this->json('PUT', "/core/v1/users/{$user->id}", $payload);
        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /*
     * Global Admin Invoked.
     */

    public function test_global_admin_can_update_service_worker()
    {
        $invoker = $user = $this->makeGlobalAdmin(factory(User::class)->create());
        Passport::actingAs($invoker);

        $service = factory(Service::class)->create();
        $user = $this->makeServiceWorker(factory(User::class)->create(), $service);

        $response = $this->json('PUT', "/core/v1/users/{$user->id}", [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'password' => 'Pa$$w0rd',
            'roles' => [
                [
                    'role' => Role::NAME_SERVICE_WORKER,
                    'service_id' => $service->id,
                ],
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'roles' => [
                [
                    'role' => Role::NAME_SERVICE_WORKER,
                    'service_id' => $service->id,
                ],
            ],
        ]);
    }

    public function test_global_admin_can_update_service_admin()
    {
        $invoker = $user = $this->makeGlobalAdmin(factory(User::class)->create());
        Passport::actingAs($invoker);

        $service = factory(Service::class)->create();
        $user = $this->makeServiceAdmin(factory(User::class)->create(), $service);

        $response = $this->json('PUT', "/core/v1/users/{$user->id}", [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'password' => 'Pa$$w0rd',
            'roles' => [
                [
                    'role' => Role::NAME_SERVICE_WORKER,
                    'service_id' => $service->id,
                ],
                [
                    'role' => Role::NAME_SERVICE_ADMIN,
                    'service_id' => $service->id,
                ],
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
        ]);
        $response->assertJsonCount(1, 'data.roles');
        $response->assertJsonFragment([
            'role' => Role::NAME_SERVICE_ADMIN,
            'service_id' => $service->id,
        ]);
    }

    public function test_global_admin_can_update_organisation_admin()
    {
        $invoker = $user = $this->makeGlobalAdmin(factory(User::class)->create());
        Passport::actingAs($invoker);

        $service = factory(Service::class)->create();
        $user = $this->makeOrganisationAdmin(factory(User::class)->create(), $service->organisation);

        $response = $this->json('PUT', "/core/v1/users/{$user->id}", [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'password' => 'Pa$$w0rd',
            'roles' => [
                [
                    'role' => Role::NAME_SERVICE_WORKER,
                    'service_id' => $service->id,
                ],
                [
                    'role' => Role::NAME_SERVICE_ADMIN,
                    'service_id' => $service->id,
                ],
                [
                    'role' => Role::NAME_ORGANISATION_ADMIN,
                    'organisation_id' => $service->organisation->id,
                ],
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
        ]);
        $response->assertJsonCount(1, 'data.roles');
        $response->assertJsonFragment([
            'role' => Role::NAME_ORGANISATION_ADMIN,
            'organisation_id' => $service->organisation->id,
        ]);
    }

    public function test_global_admin_can_update_local_admin()
    {
        $service = factory(Service::class)->create();
        $serviceAdmin = $this->makeServiceAdmin(factory(User::class)->create(), $service);
        $organisationAdmin = $this->makeOrganisationAdmin(factory(User::class)->create(), $service->organisation);
        $localAdmin = $this->makeLocalAdmin(factory(User::class)->create());
        $globalAdmin = $this->makeGlobalAdmin(factory(User::class)->create());
        $user = $this->makeLocalAdmin(factory(User::class)->create());

        $payload = [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => '01228567890',
            'password' => 'Pa$$w0rd',
            'roles' => [
                [
                    'role' => Role::NAME_ORGANISATION_ADMIN,
                    'organisation_id' => $service->organisation->id,
                ],
                ['role' => Role::NAME_LOCAL_ADMIN],
            ],
        ];

        Passport::actingAs($serviceAdmin);
        $response = $this->json('PUT', "/core/v1/users/{$user->id}", $payload);
        $response->assertStatus(Response::HTTP_FORBIDDEN);

        Passport::actingAs($organisationAdmin);
        $response = $this->json('PUT', "/core/v1/users/{$user->id}", $payload);
        $response->assertStatus(Response::HTTP_FORBIDDEN);

        Passport::actingAs($localAdmin);
        $response = $this->json('PUT', "/core/v1/users/{$user->id}", $payload);
        $response->assertStatus(Response::HTTP_FORBIDDEN);

        Passport::actingAs($globalAdmin);
        $response = $this->json('PUT', "/core/v1/users/{$user->id}", $payload);
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => '01228567890',
        ]);
        $createdUserId = $response->json('data.id');
        $createdUser = User::findOrFail($createdUserId);
        $this->assertTrue($createdUser->isOrganisationAdmin($service->organisation));
        $this->assertTrue($createdUser->isLocalAdmin());
    }

    public function test_global_admin_can_update_global_admin()
    {
        $invoker = $user = $this->makeGlobalAdmin(factory(User::class)->create());
        Passport::actingAs($invoker);

        $service = factory(Service::class)->create();
        $user = $this->makeGlobalAdmin(factory(User::class)->create());

        $response = $this->json('PUT', "/core/v1/users/{$user->id}", [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'password' => 'Pa$$w0rd',
            'roles' => [
                [
                    'role' => Role::NAME_SERVICE_WORKER,
                    'service_id' => $service->id,
                ],
                [
                    'role' => Role::NAME_SERVICE_ADMIN,
                    'service_id' => $service->id,
                ],
                [
                    'role' => Role::NAME_ORGANISATION_ADMIN,
                    'organisation_id' => $service->organisation->id,
                ],
                ['role' => Role::NAME_GLOBAL_ADMIN],
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'roles' => [
                [
                    'role' => Role::NAME_GLOBAL_ADMIN,
                ],
            ],
        ]);
    }

    public function test_global_admin_cannot_update_super_admin()
    {
        $invoker = $user = $this->makeGlobalAdmin(factory(User::class)->create());
        Passport::actingAs($invoker);

        $service = factory(Service::class)->create();
        $user = $this->makeSuperAdmin(factory(User::class)->create());

        $response = $this->json('PUT', "/core/v1/users/{$user->id}", [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'password' => 'Pa$$w0rd',
            'roles' => [
                [
                    'role' => Role::NAME_SERVICE_WORKER,
                    'service_id' => $service->id,
                ],
                [
                    'role' => Role::NAME_SERVICE_ADMIN,
                    'service_id' => $service->id,
                ],
                [
                    'role' => Role::NAME_ORGANISATION_ADMIN,
                    'organisation_id' => $service->organisation->id,
                ],
                ['role' => Role::NAME_GLOBAL_ADMIN],
                ['role' => Role::NAME_SUPER_ADMIN],
            ],
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /*
     * Super Admin Invoked.
     */

    public function test_super_admin_can_update_service_worker()
    {
        $invoker = $this->makeSuperAdmin(factory(User::class)->create());
        Passport::actingAs($invoker);

        $service = factory(Service::class)->create();
        $user = $this->makeServiceWorker(factory(User::class)->create(), $service);

        $response = $this->json('PUT', "/core/v1/users/{$user->id}", [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'password' => 'Pa$$w0rd',
            'roles' => [
                [
                    'role' => Role::NAME_SERVICE_WORKER,
                    'service_id' => $service->id,
                ],
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'roles' => [
                [
                    'role' => Role::NAME_SERVICE_WORKER,
                    'service_id' => $service->id,
                ],
            ],
        ]);
    }

    public function test_super_admin_can_update_service_admin()
    {
        $invoker = $this->makeSuperAdmin(factory(User::class)->create());
        Passport::actingAs($invoker);

        $service = factory(Service::class)->create();
        $user = $this->makeServiceAdmin(factory(User::class)->create(), $service);

        $response = $this->json('PUT', "/core/v1/users/{$user->id}", [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'password' => 'Pa$$w0rd',
            'roles' => [
                [
                    'role' => Role::NAME_SERVICE_WORKER,
                    'service_id' => $service->id,
                ],
                [
                    'role' => Role::NAME_SERVICE_ADMIN,
                    'service_id' => $service->id,
                ],
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
        ]);
        $response->assertJsonCount(1, 'data.roles');
        $response->assertJsonFragment([
            'role' => Role::NAME_SERVICE_ADMIN,
            'service_id' => $service->id,
        ]);
    }

    public function test_super_admin_can_update_organisation_admin()
    {
        $invoker = $this->makeSuperAdmin(factory(User::class)->create());
        Passport::actingAs($invoker);

        $service = factory(Service::class)->create();
        $user = $this->makeOrganisationAdmin(factory(User::class)->create(), $service->organisation);

        $response = $this->json('PUT', "/core/v1/users/{$user->id}", [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'password' => 'Pa$$w0rd',
            'roles' => [
                [
                    'role' => Role::NAME_SERVICE_WORKER,
                    'service_id' => $service->id,
                ],
                [
                    'role' => Role::NAME_SERVICE_ADMIN,
                    'service_id' => $service->id,
                ],
                [
                    'role' => Role::NAME_ORGANISATION_ADMIN,
                    'organisation_id' => $service->organisation->id,
                ],
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
        ]);
        $response->assertJsonCount(1, 'data.roles');
        $response->assertJsonFragment([
            'role' => Role::NAME_ORGANISATION_ADMIN,
            'organisation_id' => $service->organisation->id,
        ]);
    }

    public function test_super_admin_can_update_global_admin()
    {
        $invoker = $this->makeSuperAdmin(factory(User::class)->create());
        Passport::actingAs($invoker);

        $location = factory(Location::class)->create();
        $localAuthority = factory(LocalAuthority::class)->create();
        $service = factory(Service::class)->create();
        $user = $this->makeGlobalAdmin(factory(User::class)->create());

        $response = $this->json('PUT', "/core/v1/users/{$user->id}", [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'password' => 'Pa$$w0rd',
            'employer_name' => $service->organisation->name,
            'location_id' => $location->id,
            'local_authority_id' => $localAuthority->id,
            'roles' => [
                [
                    'role' => Role::NAME_SERVICE_WORKER,
                    'service_id' => $service->id,
                ],
                [
                    'role' => Role::NAME_SERVICE_ADMIN,
                    'service_id' => $service->id,
                ],
                [
                    'role' => Role::NAME_ORGANISATION_ADMIN,
                    'organisation_id' => $service->organisation->id,
                ],
                ['role' => Role::NAME_GLOBAL_ADMIN],
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'employer_name' => $service->organisation->name,
            'roles' => [
                [
                    'role' => Role::NAME_GLOBAL_ADMIN,
                ],
            ],
        ]);
        $response->assertJsonFragment([
            'address' => [
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
        $response->assertJsonFragment([
            'local_authority' => [
                'id' => $localAuthority->id,
                'name' => $localAuthority->name,
                'alt_name' => $localAuthority->alt_name,
                'code' => $localAuthority->code,
                'region' => $localAuthority->region(),
                'created_at' => $localAuthority->created_at->format(CarbonImmutable::ISO8601),
                'updated_at' => $localAuthority->updated_at->format(CarbonImmutable::ISO8601),
            ],
        ]);
    }

    public function test_super_admin_can_update_super_admin()
    {
        $invoker = $this->makeSuperAdmin(factory(User::class)->create());
        Passport::actingAs($invoker);

        $service = factory(Service::class)->create();
        $user = $this->makeSuperAdmin(factory(User::class)->create());

        $response = $this->json('PUT', "/core/v1/users/{$user->id}", [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'password' => 'Pa$$w0rd',
            'roles' => [
                [
                    'role' => Role::NAME_SERVICE_WORKER,
                    'service_id' => $service->id,
                ],
                [
                    'role' => Role::NAME_SERVICE_ADMIN,
                    'service_id' => $service->id,
                ],
                [
                    'role' => Role::NAME_ORGANISATION_ADMIN,
                    'organisation_id' => $service->organisation->id,
                ],
                ['role' => Role::NAME_GLOBAL_ADMIN],
                ['role' => Role::NAME_SUPER_ADMIN],
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'roles' => [
                [
                    'role' => Role::NAME_SUPER_ADMIN,
                ],
            ],
        ]);
    }

    public function test_super_admin_can_remove_address_from_user()
    {
        $invoker = $this->makeSuperAdmin(factory(User::class)->create());
        Passport::actingAs($invoker);

        $user = $this->makeGlobalAdmin(factory(User::class)->states('address')->create());
        $userLocationId = $user->location->id;

        $response = $this->json('PUT', "/core/v1/users/{$user->id}", [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'location_id' => null,
            'roles' => [
                ['role' => Role::NAME_GLOBAL_ADMIN],
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'address' => null,
        ]);

        $this->assertDatabaseHas(table(User::class), [
            'id' => $user->id,
            'location_id' => null,
        ]);

        $this->assertDatabaseMissing(table(Location::class), [
            'id' => $userLocationId,
        ]);
    }

    public function test_super_admin_can_remove_local_authority_from_user()
    {
        $invoker = $this->makeSuperAdmin(factory(User::class)->create());
        Passport::actingAs($invoker);

        $user = $this->makeGlobalAdmin(factory(User::class)->states('localAuthority')->create());

        $response = $this->json('PUT', "/core/v1/users/{$user->id}", [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'local_authority_id' => null,
            'roles' => [
                ['role' => Role::NAME_GLOBAL_ADMIN],
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'local_authority' => null,
        ]);

        $this->assertDatabaseHas(table(User::class), [
            'id' => $user->id,
            'local_authority_id' => null,
        ]);
    }

    /*
     * Audit.
     */

    public function test_audit_created_when_updated()
    {
        $this->fakeEvents();

        $invoker = $this->makeSuperAdmin(factory(User::class)->create());
        Passport::actingAs($invoker);

        $service = factory(Service::class)->create();
        $subject = $this->makeSuperAdmin(factory(User::class)->create());

        $this->json('PUT', "/core/v1/users/{$subject->id}", [
            'first_name' => $subject->first_name,
            'last_name' => $subject->last_name,
            'email' => $subject->email,
            'phone' => $subject->phone,
            'password' => 'Pa$$w0rd',
            'roles' => [
                [
                    'role' => Role::NAME_SERVICE_WORKER,
                    'service_id' => $service->id,
                ],
                [
                    'role' => Role::NAME_SERVICE_ADMIN,
                    'service_id' => $service->id,
                ],
                [
                    'role' => Role::NAME_ORGANISATION_ADMIN,
                    'organisation_id' => $service->organisation->id,
                ],
                ['role' => Role::NAME_GLOBAL_ADMIN],
                ['role' => Role::NAME_SUPER_ADMIN],
            ],
        ]);

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($invoker, $subject) {
            return ($event->getAction() === Audit::ACTION_UPDATE) &&
                ($event->getUser()->id === $invoker->id) &&
                ($event->getModel()->id === $subject->id);
        });
    }

    /*
     * ==================================================
     * Delete a specific user.
     * ==================================================
     */

    public function test_guest_cannot_delete_service_worker()
    {
        $service = factory(Service::class)->create();
        $subject = $this->makeServiceWorker(factory(User::class)->create(), $service);

        $response = $this->json('DELETE', "/core/v1/users/{$subject->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_delete_service_worker()
    {
        $service = factory(Service::class)->create();
        $invoker = $this->makeServiceWorker(factory(User::class)->create(), $service);
        $subject = $this->makeServiceWorker(factory(User::class)->create(), $service);
        Passport::actingAs($invoker);

        $response = $this->json('DELETE', "/core/v1/users/{$subject->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_can_delete_service_worker()
    {
        $service = factory(Service::class)->create();
        $invoker = $this->makeServiceAdmin(factory(User::class)->create(), $service);
        $subject = $this->makeServiceWorker(factory(User::class)->create(), $service);
        Passport::actingAs($invoker);

        $response = $this->json('DELETE', "/core/v1/users/{$subject->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertSoftDeleted((new User())->getTable(), ['id' => $subject->id]);
    }

    public function test_guest_cannot_delete_service_admin()
    {
        $service = factory(Service::class)->create();
        $subject = $this->makeServiceAdmin(factory(User::class)->create(), $service);

        $response = $this->json('DELETE', "/core/v1/users/{$subject->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_delete_service_admin()
    {
        $service = factory(Service::class)->create();
        $invoker = $this->makeServiceWorker(factory(User::class)->create(), $service);
        $subject = $this->makeServiceAdmin(factory(User::class)->create(), $service);
        Passport::actingAs($invoker);

        $response = $this->json('DELETE', "/core/v1/users/{$subject->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_can_delete_service_admin()
    {
        $service = factory(Service::class)->create();
        $invoker = $this->makeServiceAdmin(factory(User::class)->create(), $service);
        $subject = $this->makeServiceAdmin(factory(User::class)->create(), $service);
        Passport::actingAs($invoker);

        $response = $this->json('DELETE', "/core/v1/users/{$subject->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertSoftDeleted((new User())->getTable(), ['id' => $subject->id]);
    }

    public function test_guest_cannot_delete_organisation_admin()
    {
        $service = factory(Service::class)->create();
        $subject = $this->makeOrganisationAdmin(factory(User::class)->create(), $service->organisation);

        $response = $this->json('DELETE', "/core/v1/users/{$subject->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_delete_organisation_admin()
    {
        $service = factory(Service::class)->create();
        $invoker = $this->makeServiceWorker(factory(User::class)->create(), $service);
        $subject = $this->makeOrganisationAdmin(factory(User::class)->create(), $service->organisation);
        Passport::actingAs($invoker);

        $response = $this->json('DELETE', "/core/v1/users/{$subject->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_delete_organisation_admin()
    {
        $service = factory(Service::class)->create();
        $invoker = $this->makeServiceAdmin(factory(User::class)->create(), $service);
        $subject = $this->makeOrganisationAdmin(factory(User::class)->create(), $service->organisation);
        Passport::actingAs($invoker);

        $response = $this->json('DELETE', "/core/v1/users/{$subject->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_can_delete_organisation_admin()
    {
        $service = factory(Service::class)->create();
        $invoker = $this->makeOrganisationAdmin(factory(User::class)->create(), $service->organisation);
        $subject = $this->makeOrganisationAdmin(factory(User::class)->create(), $service->organisation);
        Passport::actingAs($invoker);

        $response = $this->json('DELETE', "/core/v1/users/{$subject->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertSoftDeleted((new User())->getTable(), ['id' => $subject->id]);
    }

    public function test_guest_cannot_delete_global_admin()
    {
        $subject = $user = $this->makeGlobalAdmin(factory(User::class)->create());

        $response = $this->json('DELETE', "/core/v1/users/{$subject->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_delete_global_admin()
    {
        $service = factory(Service::class)->create();
        $invoker = $this->makeServiceWorker(factory(User::class)->create(), $service);
        $subject = $user = $this->makeGlobalAdmin(factory(User::class)->create());
        Passport::actingAs($invoker);

        $response = $this->json('DELETE', "/core/v1/users/{$subject->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_delete_global_admin()
    {
        $service = factory(Service::class)->create();
        $invoker = $this->makeServiceAdmin(factory(User::class)->create(), $service);
        $subject = $user = $this->makeGlobalAdmin(factory(User::class)->create());
        Passport::actingAs($invoker);

        $response = $this->json('DELETE', "/core/v1/users/{$subject->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_can_delete_global_admin()
    {
        $service = factory(Service::class)->create();
        $invoker = $this->makeOrganisationAdmin(factory(User::class)->create(), $service->organisation);
        $subject = $user = $this->makeGlobalAdmin(factory(User::class)->create());
        Passport::actingAs($invoker);

        $response = $this->json('DELETE', "/core/v1/users/{$subject->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_global_admin_can_delete_global_admin()
    {
        $invoker = $user = $this->makeGlobalAdmin(factory(User::class)->create());
        $subject = $user = $this->makeGlobalAdmin(factory(User::class)->create());
        Passport::actingAs($invoker);

        $response = $this->json('DELETE', "/core/v1/users/{$subject->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertSoftDeleted((new User())->getTable(), ['id' => $subject->id]);
    }

    public function test_guest_cannot_delete_super_admin()
    {
        $subject = $user = $this->makeGlobalAdmin(factory(User::class)->create());

        $response = $this->json('DELETE', "/core/v1/users/{$subject->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_delete_super_admin()
    {
        $service = factory(Service::class)->create();
        $invoker = $this->makeServiceWorker(factory(User::class)->create(), $service);
        $subject = $this->makeSuperAdmin(factory(User::class)->create());
        Passport::actingAs($invoker);

        $response = $this->json('DELETE', "/core/v1/users/{$subject->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_delete_super_admin()
    {
        $service = factory(Service::class)->create();
        $invoker = $this->makeServiceAdmin(factory(User::class)->create(), $service);
        $subject = $this->makeSuperAdmin(factory(User::class)->create());
        Passport::actingAs($invoker);

        $response = $this->json('DELETE', "/core/v1/users/{$subject->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_can_delete_super_admin()
    {
        $service = factory(Service::class)->create();
        $invoker = $this->makeOrganisationAdmin(factory(User::class)->create(), $service->organisation);
        $subject = $this->makeSuperAdmin(factory(User::class)->create());
        Passport::actingAs($invoker);

        $response = $this->json('DELETE', "/core/v1/users/{$subject->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_global_admin_cannot_delete_super_admin()
    {
        $invoker = $user = $this->makeGlobalAdmin(factory(User::class)->create());
        $subject = $this->makeSuperAdmin(factory(User::class)->create());
        Passport::actingAs($invoker);

        $response = $this->json('DELETE', "/core/v1/users/{$subject->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_super_admin_can_delete_super_admin()
    {
        $invoker = $this->makeSuperAdmin(factory(User::class)->create());
        $subject = $this->makeSuperAdmin(factory(User::class)->create());
        Passport::actingAs($invoker);

        $response = $this->json('DELETE', "/core/v1/users/{$subject->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertSoftDeleted((new User())->getTable(), ['id' => $subject->id]);
    }

    public function test_audit_created_when_deleted()
    {
        $this->fakeEvents();

        $invoker = $this->makeSuperAdmin(factory(User::class)->create());
        $subject = $this->makeSuperAdmin(factory(User::class)->create());
        Passport::actingAs($invoker);

        $this->json('DELETE', "/core/v1/users/{$subject->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($invoker, $subject) {
            return ($event->getAction() === Audit::ACTION_DELETE) &&
                ($event->getUser()->id === $invoker->id) &&
                ($event->getModel()->id === $subject->id);
        });
    }

    /**
     * Bulk import Users
     */

    public function test_only_super_admin_can_bulk_import()
    {
        Storage::fake('local');
        $service = factory(Service::class)->create();

        $users = factory(User::class, 2)->make();

        $this->createUserSpreadsheets($users);

        $data = [
            'spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xls'))),
        ];

        $this->json('POST', "/core/v1/users/import", $data)->assertStatus(Response::HTTP_UNAUTHORIZED);

        Passport::actingAs($this->makeServiceWorker(factory(User::class)->create(), $service));

        $this->json('POST', "/core/v1/users/import", $data)->assertStatus(Response::HTTP_FORBIDDEN);

        Passport::actingAs($this->makeServiceAdmin(factory(User::class)->create(), $service));

        $this->json('POST', "/core/v1/users/import", $data)->assertStatus(Response::HTTP_FORBIDDEN);

        Passport::actingAs($this->makeOrganisationAdmin(factory(User::class)->create(), $service->organisation));

        $this->json('POST', "/core/v1/users/import", $data)->assertStatus(Response::HTTP_FORBIDDEN);

        Passport::actingAs($this->makeLocalAdmin(factory(User::class)->create()));

        $this->json('POST', "/core/v1/users/import", $data)->assertStatus(Response::HTTP_FORBIDDEN);

        Passport::actingAs($this->makeGlobalAdmin(factory(User::class)->create()));

        $this->json('POST', "/core/v1/users/import", $data)->assertStatus(Response::HTTP_FORBIDDEN);

        Passport::actingAs($this->makeSuperAdmin(factory(User::class)->create()));

        $response = $this->json('POST', "/core/v1/users/import", $data);

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

        Passport::actingAs($this->makeSuperAdmin(factory(User::class)->create()));

        foreach ($invalidFieldTypes as $data) {
            $response = $this->json('POST', "/core/v1/users/import", $data);
            $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $users = factory(User::class, 2)->make();

        $this->createUserSpreadsheets($users);

        $response = $this->json('POST', "/core/v1/users/import", ['spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xls')))]);
        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJson([
            'data' => [
                'imported_row_count' => 2,
            ],
        ]);

        $users = factory(User::class, 2)->make();

        $this->createUserSpreadsheets($users);

        $response = $this->json('POST', "/core/v1/users/import", ['spreadsheet' => 'data:application/octet-stream;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xlsx')))]);
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

        $users = factory(User::class, 2)->make();

        $users->get(0)->setAttribute('first_name', '');
        $users->get(0)->setAttribute('last_name', 6);
        $users->get(1)->setAttribute('email', 'notanemailaddress');
        $users->get(1)->setAttribute('phone', '987412365');

        $this->createUserSpreadsheets($users);

        Passport::actingAs($this->makeSuperAdmin(factory(User::class)->create()));

        $response = $this->json('POST', "/core/v1/users/import", ['spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xls')))]);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJson([
            'data' => [
                'errors' => [
                    'spreadsheet' => [
                        [
                            'row' => [],
                            'errors' => [
                                'first_name' => [],
                                'last_name' => [],
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

        $response = $this->json('POST', "/core/v1/users/import", ['spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xlsx')))]);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJson([
            'data' => [
                'errors' => [
                    'spreadsheet' => [
                        [
                            'row' => [],
                            'errors' => [
                                'first_name' => [],
                                'last_name' => [],
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

    public function test_additional_user_fields_can_be_imported()
    {
        Storage::fake('local');

        $users = factory(User::class, 5)->states('employed')->make();

        $this->createUserSpreadsheets($users);

        Passport::actingAs($this->makeSuperAdmin(factory(User::class)->create()));

        $response = $this->json('POST', "/core/v1/users/import", ['spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xls')))]);
        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJson([
            'data' => [
                'imported_row_count' => 5,
            ],
        ]);

        $this->assertInstanceOf(Location::class, User::where('email', $users->get(0)->email)->first()->location);

    }

    public function test_duplicate_import_users_are_detected()
    {
        Storage::fake('local');

        $user = $this->makeSuperAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $headers = [
            'first_name',
            'last_name',
            'email',
            'phone',
            'employer_name',
            'address_line_1',
            'address_line_2',
            'address_line_3',
            'city',
            'county',
            'postcode',
            'country',
        ];

        $existingUser = factory(User::class)->create(['email' => 'test@example.org']);

        $users = collect([
            factory(User::class)->make(['email' => 'test@example.org']),
            factory(User::class)->make(),
            factory(User::class)->make(),
        ]);

        $this->createUserSpreadsheets($users);

        Passport::actingAs($this->makeSuperAdmin(factory(User::class)->create()));

        $response = $this->json('POST', "/core/v1/users/import", [
            'spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xls'))),
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $response->assertJson([
            'data' => [
                'errors' => [
                    'spreadsheet' => [
                        [
                            'row' => collect($users->get(0)->getAttributes())->only($headers)->all(),
                            'errors' => [
                                'email' => ['This email address has already been taken.'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /*
     * ==================================================
     * Helpers.
     * ==================================================
     */

    /**
     * @param array $roles
     * @return array
     */
    protected function getCreateUserPayload(array $roles): array
    {
        return [
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->safeEmail,
            'phone' => random_uk_phone(),
            'password' => 'Pa$$w0rd',
            'roles' => $roles,
            'employer_name' => null,
        ];
    }

    /**
     * @param array $roles
     * @return array
     */
    protected function getUpdateUserPayload(array $roles): array
    {
        return [
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->safeEmail,
            'phone' => random_uk_phone(),
            'roles' => $roles,
            'employer_name' => null,
        ];
    }

    /**
     * Create spreadsheets of users
     *
     * @param Array $users
     * @return null
     **/
    public function createUserSpreadsheets(\Illuminate\Support\Collection $users)
    {
        $headers = [
            'first_name',
            'last_name',
            'email',
            'phone',
            'employer_name',
            'address_line_1',
            'address_line_2',
            'address_line_3',
            'city',
            'county',
            'postcode',
            'country',
        ];

        $flattenedUsers = [];

        foreach ($users as $user) {
            $userAttributes = collect($user->toArray());
            $userAttributes = $userAttributes->merge(factory(Location::class)->make()->toArray());
            $flattenedUsers[] = $userAttributes->only($headers);
        }

        $spreadsheet = \Tests\Integration\SpreadsheetParserTest::createSpreadsheets($flattenedUsers, $headers);
        \Tests\Integration\SpreadsheetParserTest::writeSpreadsheetsToDisk($spreadsheet, 'test.xlsx', 'test.xls');
    }
}
