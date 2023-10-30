<?php

namespace Tests\Feature;

use App\Events\EndpointHit;
use App\Models\Audit;
use App\Models\Organisation;
use App\Models\Service;
use App\Models\Taxonomy;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Laravel\Passport\Passport;
use Tests\TestCase;

class TaxonomyOrganisationsTest extends TestCase
{
    /*
     * List all the organisation taxonomies.
     */

    /**
     * @test
     */
    public function guest_can_list_them()
    {
        $taxonomy = $this->createTaxonomyOrganisation();

        $response = $this->json('GET', '/core/v1/taxonomies/organisations');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            [
                'id' => $taxonomy->id,
                'slug' => $taxonomy->slug,
                'name' => $taxonomy->name,
                'order' => $taxonomy->order,
                'created_at' => $taxonomy->created_at->format(CarbonImmutable::ISO8601),
                'updated_at' => $taxonomy->updated_at->format(CarbonImmutable::ISO8601),
            ],
        ]);
    }

    /**
     * @test
     */
    public function audit_created_when_listed()
    {
        $this->fakeEvents();

        $this->json('GET', '/core/v1/taxonomies/organisations');

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) {
            return $event->getAction() === Audit::ACTION_READ;
        });
    }

    /*
     * Create an organisation taxonomy.
     */

    /**
     * @test
     */
    public function guest_cannot_create_one()
    {
        $response = $this->json('POST', '/core/v1/taxonomies/organisations');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @test
     */
    public function service_worker_cannot_create_one()
    {
        $service = Service::factory()->create();
        $user = User::factory()->create()->makeServiceWorker($service);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/taxonomies/organisations');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function service_admin_cannot_create_one()
    {
        $service = Service::factory()->create();
        $user = User::factory()->create()->makeServiceAdmin($service);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/taxonomies/organisations');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function organisation_admin_cannot_create_one()
    {
        $organisation = Organisation::factory()->create();
        $user = User::factory()->create()->makeOrganisationAdmin($organisation);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/taxonomies/organisations');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function global_admin_cannot_create_one()
    {
        $user = User::factory()->create()->makeGlobalAdmin();

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/taxonomies/organisations');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function super_admin_can_create_one()
    {
        $user = User::factory()->create()->makeSuperAdmin();
        $siblingCount = Taxonomy::organisation()->children()->count();
        $payload = [
            'name' => 'PHPUnit Taxonomy Organisation Test',
            'order' => $siblingCount + 1,
        ];

        Passport::actingAs($user);
        $response = $this->json('POST', '/core/v1/taxonomies/organisations', $payload);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonFragment($payload);
    }

    /**
     * @test
     */
    public function order_is_updated_when_created_at_beginning()
    {
        $this->createTaxonomyOrganisation(['slug' => 'org']);
        $this->createTaxonomyOrganisation(['slug' => 'org-1']);
        $this->createTaxonomyOrganisation(['slug' => 'org-2']);

        $user = User::factory()->create()->makeSuperAdmin();
        $taxonomyOrganisation = Taxonomy::organisation()->children()->orderBy('order')->get();
        $payload = [
            'name' => 'PHPUnit Taxonomy Organisation Test',
            'order' => 1,
        ];

        Passport::actingAs($user);
        $response = $this->json('POST', '/core/v1/taxonomies/organisations', $payload);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonFragment($payload);
        foreach ($taxonomyOrganisation as $organisation) {
            $this->assertDatabaseHas(
                (new Taxonomy())->getTable(),
                ['id' => $organisation->id, 'order' => $organisation->order + 1]
            );
        }
    }

    /**
     * @test
     */
    public function order_is_updated_when_created_at_middle()
    {
        $this->createTaxonomyOrganisation(['slug' => 'org']);
        $this->createTaxonomyOrganisation(['slug' => 'org-1']);
        $this->createTaxonomyOrganisation(['slug' => 'org-2']);

        $user = User::factory()->create()->makeSuperAdmin();
        $taxonomyOrganisations = Taxonomy::organisation()->children()->orderBy('order')->get();
        $payload = [
            'name' => 'PHPUnit Taxonomy Organisation Test',
            'order' => 2,
        ];

        Passport::actingAs($user);
        $response = $this->json('POST', '/core/v1/taxonomies/organisations', $payload);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonFragment($payload);
        foreach ($taxonomyOrganisations as $organisation) {
            if ($organisation->order < 2) {
                $this->assertDatabaseHas(
                    (new Taxonomy())->getTable(),
                    ['id' => $organisation->id, 'order' => $organisation->order]
                );
            } else {
                $this->assertDatabaseHas(
                    (new Taxonomy())->getTable(),
                    ['id' => $organisation->id, 'order' => $organisation->order + 1]
                );
            }
        }
    }

    /**
     * @test
     */
    public function order_is_updated_when_created_at_end()
    {
        $this->createTaxonomyOrganisation(['slug' => 'org']);
        $this->createTaxonomyOrganisation(['slug' => 'org-1']);
        $this->createTaxonomyOrganisation(['slug' => 'org-2']);

        $user = User::factory()->create()->makeSuperAdmin();
        $taxonomyOrganisations = Taxonomy::organisation()->children()->orderBy('order')->get();
        $payload = [
            'name' => 'PHPUnit Taxonomy Organisation Test',
            'order' => $taxonomyOrganisations->count() + 1,
        ];

        Passport::actingAs($user);
        $response = $this->json('POST', '/core/v1/taxonomies/organisations', $payload);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonFragment($payload);
        foreach ($taxonomyOrganisations as $organisation) {
            $this->assertDatabaseHas(
                (new Taxonomy())->getTable(),
                ['id' => $organisation->id, 'order' => $organisation->order]
            );
        }
    }

    /**
     * @test
     */
    public function order_cannot_be_less_than_1_when_created()
    {
        $user = User::factory()->create()->makeSuperAdmin();
        $payload = [
            'name' => 'PHPUnit Taxonomy Organisation Test',
            'order' => 0,
        ];

        Passport::actingAs($user);
        $response = $this->json('POST', '/core/v1/taxonomies/organisations', $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @test
     */
    public function order_cannot_be_greater_than_count_plus_1_when_created()
    {
        $user = User::factory()->create()->makeSuperAdmin();
        $siblingCount = Taxonomy::organisation()->children()->count();
        $payload = [
            'name' => 'PHPUnit Taxonomy Organisation Test',
            'order' => $siblingCount + 2,
        ];

        Passport::actingAs($user);
        $response = $this->json('POST', '/core/v1/taxonomies/organisations', $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @test
     */
    public function audit_created_when_created()
    {
        $this->fakeEvents();

        $user = User::factory()->create()->makeSuperAdmin();
        $siblingCount = Taxonomy::organisation()->children()->count();

        Passport::actingAs($user);
        $response = $this->json('POST', '/core/v1/taxonomies/organisations', [
            'name' => 'PHPUnit Taxonomy Organisation Test',
            'order' => $siblingCount + 1,
        ]);

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $response) {
            return ($event->getAction() === Audit::ACTION_CREATE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $this->getResponseContent($response)['data']['id']);
        });
    }

    /*
     * Get a specific organisation taxonomy.
     */

    /**
     * @test
     */
    public function guest_can_view_one()
    {
        $taxonomy = $this->createTaxonomyOrganisation();

        $response = $this->json('GET', "/core/v1/taxonomies/organisations/{$taxonomy->id}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJson([
            'data' => [
                'id' => $taxonomy->id,
                'slug' => $taxonomy->slug,
                'name' => $taxonomy->name,
                'order' => $taxonomy->order,
                'created_at' => $taxonomy->created_at->format(CarbonImmutable::ISO8601),
                'updated_at' => $taxonomy->updated_at->format(CarbonImmutable::ISO8601),
            ],
        ]);
    }

    /**
     * @test
     */
    public function audit_created_when_viewed()
    {
        $this->fakeEvents();

        $taxonomy = $this->createTaxonomyOrganisation();

        $this->json('GET', "/core/v1/taxonomies/organisations/{$taxonomy->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($taxonomy) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getModel()->id === $taxonomy->id);
        });
    }

    /*
     * Update a specific organisation taxonomy.
     */

    /**
     * @test
     */
    public function guest_cannot_update_one()
    {
        $organisation = $this->createTaxonomyOrganisation();

        $response = $this->json('PUT', "/core/v1/taxonomies/organisations/{$organisation->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @test
     */
    public function service_worker_cannot_update_one()
    {
        $service = Service::factory()->create();
        $user = User::factory()->create()->makeServiceWorker($service);
        $organisation = $this->createTaxonomyOrganisation();

        Passport::actingAs($user);
        $response = $this->json('PUT', "/core/v1/taxonomies/organisations/{$organisation->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function service_admin_cannot_update_one()
    {
        $service = Service::factory()->create();
        $user = User::factory()->create()->makeServiceAdmin($service);
        $organisation = $this->createTaxonomyOrganisation();

        Passport::actingAs($user);
        $response = $this->json('PUT', "/core/v1/taxonomies/organisations/{$organisation->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function organisation_admin_cannot_update_one()
    {
        $organisation = Organisation::factory()->create();
        $user = User::factory()->create()->makeOrganisationAdmin($organisation);
        $organisation = $this->createTaxonomyOrganisation();

        Passport::actingAs($user);
        $response = $this->json('PUT', "/core/v1/taxonomies/organisations/{$organisation->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function global_admin_cannot_update_one()
    {
        $user = User::factory()->create()->makeGlobalAdmin();
        $organisation = $this->createTaxonomyOrganisation();

        Passport::actingAs($user);
        $response = $this->json('PUT', "/core/v1/taxonomies/organisations/{$organisation->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function super_admin_can_update_one()
    {
        $user = User::factory()->create()->makeSuperAdmin();
        $organisation = $this->createTaxonomyOrganisation();
        $payload = [
            'name' => 'PHPUnit Test Organisation',
            'order' => $organisation->order,
        ];

        Passport::actingAs($user);
        $response = $this->json('PUT', "/core/v1/taxonomies/organisations/{$organisation->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment($payload);
    }

    /**
     * @test
     */
    public function order_is_updated_when_updated_to_beginning()
    {
        $user = User::factory()->create()->makeSuperAdmin();
        Passport::actingAs($user);

        $organisationOne = $this->createTaxonomyOrganisation(['slug' => 'one', 'name' => 'One', 'order' => 1]);
        $organisationTwo = $this->createTaxonomyOrganisation(['slug' => 'two', 'name' => 'Two', 'order' => 2]);
        $organisationThree = $this->createTaxonomyOrganisation(['slug' => 'three', 'name' => 'Three', 'order' => 3]);

        $response = $this->json('PUT', "/core/v1/taxonomies/organisations/{$organisationTwo->id}", [
            'name' => $organisationTwo->name,
            'order' => 1,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas((new Taxonomy())->getTable(), ['id' => $organisationOne->id, 'order' => 2]);
        $this->assertDatabaseHas((new Taxonomy())->getTable(), ['id' => $organisationTwo->id, 'order' => 1]);
        $this->assertDatabaseHas((new Taxonomy())->getTable(), ['id' => $organisationThree->id, 'order' => 3]);
    }

    /**
     * @test
     */
    public function order_is_updated_when_updated_to_middle()
    {
        $user = User::factory()->create()->makeSuperAdmin();
        Passport::actingAs($user);

        $organisationOne = $this->createTaxonomyOrganisation(['slug' => 'one', 'name' => 'One', 'order' => 1]);
        $organisationTwo = $this->createTaxonomyOrganisation(['slug' => 'two', 'name' => 'Two', 'order' => 2]);
        $organisationThree = $this->createTaxonomyOrganisation(['slug' => 'three', 'name' => 'Three', 'order' => 3]);

        $response = $this->json('PUT', "/core/v1/taxonomies/organisations/{$organisationOne->id}", [
            'name' => $organisationOne->name,
            'order' => 2,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas((new Taxonomy())->getTable(), ['id' => $organisationOne->id, 'order' => 2]);
        $this->assertDatabaseHas((new Taxonomy())->getTable(), ['id' => $organisationTwo->id, 'order' => 1]);
        $this->assertDatabaseHas((new Taxonomy())->getTable(), ['id' => $organisationThree->id, 'order' => 3]);
    }

    /**
     * @test
     */
    public function order_is_updated_when_updated_to_end()
    {
        $user = User::factory()->create()->makeSuperAdmin();
        Passport::actingAs($user);

        $organisationOne = $this->createTaxonomyOrganisation(['slug' => 'one', 'name' => 'One', 'order' => 1]);
        $organisationTwo = $this->createTaxonomyOrganisation(['slug' => 'two', 'name' => 'Two', 'order' => 2]);
        $organisationThree = $this->createTaxonomyOrganisation(['slug' => 'three', 'name' => 'Three', 'order' => 3]);

        $response = $this->json('PUT', "/core/v1/taxonomies/organisations/{$organisationTwo->id}", [
            'name' => $organisationTwo->name,
            'order' => 3,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas((new Taxonomy())->getTable(), ['id' => $organisationOne->id, 'order' => 1]);
        $this->assertDatabaseHas((new Taxonomy())->getTable(), ['id' => $organisationTwo->id, 'order' => 3]);
        $this->assertDatabaseHas((new Taxonomy())->getTable(), ['id' => $organisationThree->id, 'order' => 2]);
    }

    /**
     * @test
     */
    public function order_cannot_be_less_than_1_when_updated()
    {
        $user = User::factory()->create()->makeSuperAdmin();
        Passport::actingAs($user);

        $organisation = $this->createTaxonomyOrganisation();

        $response = $this->json('PUT', "/core/v1/taxonomies/organisations/{$organisation->id}", [
            'name' => $organisation->name,
            'order' => 0,
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @test
     */
    public function order_cannot_be_greater_than_count_plus_1_when_updated()
    {
        $user = User::factory()->create()->makeSuperAdmin();
        Passport::actingAs($user);

        $organisation = $this->createTaxonomyOrganisation(['slug' => 'one', 'name' => 'One', 'order' => 1]);
        $this->createTaxonomyOrganisation(['slug' => 'two', 'name' => 'Two', 'order' => 2]);
        $this->createTaxonomyOrganisation(['slug' => 'three', 'name' => 'Three', 'order' => 3]);

        $response = $this->json('PUT', "/core/v1/taxonomies/organisations/{$organisation->id}", [
            'name' => $organisation->name,
            'order' => 4,
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @test
     */
    public function audit_created_when_updated()
    {
        $this->fakeEvents();

        $user = User::factory()->create()->makeSuperAdmin();
        $organisation = $this->createTaxonomyOrganisation();

        Passport::actingAs($user);
        $this->json('PUT', "/core/v1/taxonomies/organisations/{$organisation->id}", [
            'name' => 'PHPUnit Test Organisation',
            'order' => $organisation->order,
        ]);

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $organisation) {
            return ($event->getAction() === Audit::ACTION_UPDATE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $organisation->id);
        });
    }

    /*
     * Delete a specific organisation taxonomy.
     */

    /**
     * @test
     */
    public function guest_cannot_delete_one()
    {
        $organisation = $this->createTaxonomyOrganisation();

        $response = $this->json('DELETE', "/core/v1/taxonomies/organisations/{$organisation->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @test
     */
    public function service_worker_cannot_delete_one()
    {
        $service = Service::factory()->create();
        $user = User::factory()->create()->makeServiceWorker($service);
        $organisation = $this->createTaxonomyOrganisation();

        Passport::actingAs($user);
        $response = $this->json('DELETE', "/core/v1/taxonomies/organisations/{$organisation->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function service_admin_cannot_delete_one()
    {
        $service = Service::factory()->create();
        $user = User::factory()->create()->makeServiceAdmin($service);
        $organisation = $this->createTaxonomyOrganisation();

        Passport::actingAs($user);
        $response = $this->json('DELETE', "/core/v1/taxonomies/organisations/{$organisation->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function organisation_admin_cannot_delete_one()
    {
        $organisation = Organisation::factory()->create();
        $user = User::factory()->create()->makeOrganisationAdmin($organisation);
        $organisation = $this->createTaxonomyOrganisation();

        Passport::actingAs($user);
        $response = $this->json('DELETE', "/core/v1/taxonomies/organisations/{$organisation->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function global_admin_cannot_delete_one()
    {
        $organisation = Organisation::factory()->create();
        $user = User::factory()->create()->makeGlobalAdmin();
        $organisation = $this->createTaxonomyOrganisation();

        Passport::actingAs($user);
        $response = $this->json('DELETE', "/core/v1/taxonomies/organisations/{$organisation->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function super_admin_can_delete_one()
    {
        $user = User::factory()->create()->makeSuperAdmin();
        $organisation = $this->createTaxonomyOrganisation();

        Passport::actingAs($user);
        $response = $this->json('DELETE', "/core/v1/taxonomies/organisations/{$organisation->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing((new Taxonomy())->getTable(), ['id' => $organisation->id]);
    }

    /**
     * @test
     */
    public function audit_created_when_deleted()
    {
        $this->fakeEvents();

        $user = User::factory()->create()->makeSuperAdmin();
        $organisation = $this->createTaxonomyOrganisation();

        Passport::actingAs($user);
        $this->json('DELETE', "/core/v1/taxonomies/organisations/{$organisation->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $organisation) {
            return ($event->getAction() === Audit::ACTION_DELETE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $organisation->id);
        });
    }

    /*
     * Helpers.
     */

    protected function createTaxonomyOrganisation(array $data = []): Taxonomy
    {
        $count = Taxonomy::organisation()->children()->count();

        return Taxonomy::organisation()->children()->create(array_merge([
            'slug' => 'phpunit-organisation',
            'name' => 'PHPUnit Organisation',
            'order' => $count + 1,
            'depth' => 1,
        ], $data));
    }
}
