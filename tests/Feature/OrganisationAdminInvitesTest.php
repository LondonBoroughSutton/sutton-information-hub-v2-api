<?php

namespace Tests\Feature;

use App\Events\EndpointHit;
use App\Models\Audit;
use App\Models\Organisation;
use App\Models\OrganisationAdminInvite;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Laravel\Passport\Passport;
use Tests\TestCase;

class OrganisationAdminInvitesTest extends TestCase
{
    /*
     * Create an organisation admin invite.
     */

    public function test_can_create_single_invite()
    {
        $organisation = factory(Organisation::class)->create();

        Passport::actingAs(
            $this->makeSuperAdmin(factory(User::class)->create())
        );

        $response = $this->postJson('/core/v1/organisation-admin-invites', [
            'organisations' => [
                [
                    'organisation_id' => $organisation->id,
                    'use_email' => false,
                ],
            ],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment([
            'organisation_id' => $organisation->id,
            'email' => null,
        ]);
    }

    public function test_can_create_single_invite_with_email()
    {
        $organisation = factory(Organisation::class)->create([
            'email' => 'john.doe@example.com',
        ]);

        Passport::actingAs(
            $this->makeSuperAdmin(factory(User::class)->create())
        );

        $response = $this->postJson('/core/v1/organisation-admin-invites', [
            'organisations' => [
                [
                    'organisation_id' => $organisation->id,
                    'use_email' => true,
                ],
            ],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment([
            'organisation_id' => $organisation->id,
            'email' => 'john.doe@example.com',
        ]);
    }

    public function test_can_create_multiple_invites()
    {
        $organisationOne = factory(Organisation::class)->create();
        $organisationTwo = factory(Organisation::class)->create();

        Passport::actingAs(
            $this->makeSuperAdmin(factory(User::class)->create())
        );

        $response = $this->postJson('/core/v1/organisation-admin-invites', [
            'organisations' => [
                [
                    'organisation_id' => $organisationOne->id,
                    'use_email' => false,
                ],
                [
                    'organisation_id' => $organisationTwo->id,
                    'use_email' => false,
                ],
            ],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment([
            'organisation_id' => $organisationOne->id,
            'email' => null,
        ]);
        $response->assertJsonFragment([
            'organisation_id' => $organisationTwo->id,
            'email' => null,
        ]);
    }

    public function test_can_create_multiple_invites_some_with_email()
    {
        $organisationOne = factory(Organisation::class)->create();
        $organisationTwo = factory(Organisation::class)->create([
            'email' => 'john.doe@example.com',
        ]);

        Passport::actingAs(
            $this->makeSuperAdmin(factory(User::class)->create())
        );

        $response = $this->postJson('/core/v1/organisation-admin-invites', [
            'organisations' => [
                [
                    'organisation_id' => $organisationOne->id,
                    'use_email' => false,
                ],
                [
                    'organisation_id' => $organisationTwo->id,
                    'use_email' => true,
                ],
            ],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment([
            'organisation_id' => $organisationOne->id,
            'email' => null,
        ]);
        $response->assertJsonFragment([
            'organisation_id' => $organisationTwo->id,
            'email' => 'john.doe@example.com',
        ]);
    }

    public function test_create_with_email_for_organisations_missing_email_are_ignored()
    {
        $organisation = factory(Organisation::class)->create([
            'email' => null,
        ]);

        Passport::actingAs(
            $this->makeSuperAdmin(factory(User::class)->create())
        );

        $response = $this->postJson('/core/v1/organisation-admin-invites', [
            'organisations' => [
                [
                    'organisation_id' => $organisation->id,
                    'use_email' => true,
                ],
            ],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonCount(0, 'data');
    }

    public function test_can_create_no_invites()
    {
        Passport::actingAs(
            $this->makeSuperAdmin(factory(User::class)->create())
        );

        $response = $this->postJson('/core/v1/organisation-admin-invites', [
            'organisations' => [],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonCount(0, 'data');
    }

    public function test_guest_cannot_create_invite()
    {
        $response = $this->postJson('/core/v1/organisation-admin-invites', [
            'organisations' => [],
        ]);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_create_invite()
    {
        $service = factory(Service::class)->create();

        Passport::actingAs(
            $this->makeServiceWorker(factory(User::class)->create(), $service)
        );

        $response = $this->postJson('/core/v1/organisation-admin-invites', [
            'organisations' => [],
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_create_invite()
    {
        $service = factory(Service::class)->create();

        Passport::actingAs(
            $this->makeServiceAdmin(factory(User::class)->create(), $service)
        );

        $response = $this->postJson('/core/v1/organisation-admin-invites', [
            'organisations' => [],
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_cannot_create_invite()
    {
        $organisation = factory(Organisation::class)->create();

        Passport::actingAs(
            $this->makeOrganisationAdmin(factory(User::class)->create(), $organisation)
        );

        $response = $this->postJson('/core/v1/organisation-admin-invites', [
            'organisations' => [],
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_global_admin_cannot_create_invite()
    {
        Passport::actingAs(
            $this->makeGlobalAdmin(factory(User::class)->create())
        );

        $response = $this->postJson('/core/v1/organisation-admin-invites', [
            'organisations' => [],
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_create_creates_audit()
    {
        $this->fakeEvents();

        $organisation = factory(Organisation::class)->create();

        Passport::actingAs(
            $this->makeSuperAdmin(factory(User::class)->create())
        );

        $this->postJson('/core/v1/organisation-admin-invites', [
            'organisations' => [
                [
                    'organisation_id' => $organisation->id,
                    'use_email' => false,
                ],
            ],
        ]);

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) {
            return ($event->getAction() === Audit::ACTION_CREATE);
        });
    }

    /*
     * View an organisation admin invite.
     */

    public function test_guest_can_view_invite()
    {
        $organisationAdminInvite = factory(OrganisationAdminInvite::class)->create();

        $response = $this->getJson("/core/v1/organisation-admin-invites/{$organisationAdminInvite->id}");

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $organisationAdminInvite->id,
            'organisation_id' => $organisationAdminInvite->organisation_id,
            'email' => $organisationAdminInvite->email,
            'created_at' => $organisationAdminInvite->created_at->format(CarbonImmutable::ISO8601),
            'updated_at' => $organisationAdminInvite->updated_at->format(CarbonImmutable::ISO8601),
        ]);
    }

    public function test_view_creates_audit()
    {
        $this->fakeEvents();

        $organisationAdminInvite = factory(OrganisationAdminInvite::class)->create();

        $this->getJson("/core/v1/organisation-admin-invites/{$organisationAdminInvite->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($organisationAdminInvite) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getModel()->is($organisationAdminInvite));
        });
    }

    /*
     * Submit an organisation admin invite.
     */

    public function test_guest_can_submit_invite()
    {
        $organisationAdminInvite = factory(OrganisationAdminInvite::class)->create();

        $response = $this->postJson("/core/v1/organisation-admin-invites/{$organisationAdminInvite->id}/submit", [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone' => null,
            'password' => 'Pa$$w0rd',
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJson([
            'message' => 'A confirmation email will be sent shortly.',
        ]);
    }

    public function test_submit_creates_audit()
    {
        $this->fakeEvents();

        $organisationAdminInvite = factory(OrganisationAdminInvite::class)->create();

        $this->postJson("/core/v1/organisation-admin-invites/{$organisationAdminInvite->id}/submit", [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone' => null,
            'password' => 'Pa$$w0rd',
        ]);

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) {
            return ($event->getAction() === Audit::ACTION_CREATE);
        });
    }
}
