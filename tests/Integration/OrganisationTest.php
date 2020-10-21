<?php

namespace Tests\Integration;

use App\Models\File;
use App\Models\Location;
use App\Models\Organisation;
use App\Models\OrganisationAdminInvite;
use App\Models\PendingOrganisationAdmin;
use App\Models\SocialMedia;
use App\Models\User;
use Tests\TestCase;

class OrganisationTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_persist_and_retrieve_records()
    {
        factory(Organisation::class, 10)->create();

        $organisations = Organisation::all();

        $this->assertCount(10, $organisations);
    }

    /**
     * @test
     */
    public function it_can_have_an_associated_logo()
    {
        $organisation = factory(Organisation::class)->states('logo')->create();

        $this->assertInstanceOf(File::class, $organisation->logoFile);
    }

    /**
     * @test
     */
    public function it_can_have_associated_social_media()
    {
        $organisation = factory(Organisation::class)->states('social')->create();

        $this->assertInstanceOf(SocialMedia::class, $organisation->socialMedias->first());
    }

    /**
     * @test
     */
    public function it_can_have_an_associated_location()
    {
        $organisation = factory(Organisation::class)->states('location')->create();

        $this->assertInstanceOf(Location::class, $organisation->location);
    }

    public function test_organisation_admin_invite_status_is_none_when_created()
    {
        $organisation = factory(Organisation::class)->create();

        $this->assertEquals(Organisation::ADMIN_INVITE_STATUS_NONE, $organisation->admin_invite_status);
    }

    public function test_organisation_admin_invite_status_is_invited_when_invite_sent()
    {
        $organisation = factory(Organisation::class)->create();
        factory(OrganisationAdminInvite::class)->states('email')->create([
            'organisation_id' => $organisation->id,
        ]);

        $this->assertEquals(Organisation::ADMIN_INVITE_STATUS_INVITED, $organisation->fresh()->admin_invite_status);
    }

    public function test_organisation_admin_invite_status_is_pending_when_invite_submitted()
    {
        $organisation = factory(Organisation::class)->create();
        factory(PendingOrganisationAdmin::class)->create([
            'organisation_id' => $organisation->id,
        ]);

        $this->assertEquals(Organisation::ADMIN_INVITE_STATUS_PENDING, $organisation->fresh()->admin_invite_status);
    }

    public function test_organisation_admin_invite_status_is_confirmed_when_pending_email_is_confirmed()
    {
        $organisation = factory(Organisation::class)->create();
        factory(User::class)->create()->makeOrganisationAdmin($organisation);

        $this->assertEquals(Organisation::ADMIN_INVITE_STATUS_CONFIRMED, $organisation->fresh()->admin_invite_status);
    }

    public function test_organisation_scope_has_admin_returns_only_organisations_with_non_global_admins()
    {
        $organisations = factory(Organisation::class, 2)->create();
        factory(User::class)->create()->makeGlobalAdmin();

        factory(User::class)->create()->makeOrganisationAdmin($organisations->get(0));

        $adminOrganisations = Organisation::select(table(Organisation::class, '*'))->hasAdmin()->get();

        $this->assertCount(1, $adminOrganisations);

        $this->assertEquals($organisations->get(0)->id, $adminOrganisations[0]->id);
    }

    public function test_organisation_scope_has_no_admin_returns_only_organisations_with_no_non_global_admins()
    {
        $organisations = factory(Organisation::class, 2)->create();
        factory(User::class)->create()->makeGlobalAdmin();

        factory(User::class)->create()->makeOrganisationAdmin($organisations->get(0));

        $adminOrganisations = Organisation::select(table(Organisation::class, '*'))->hasNoAdmin()->get();

        $this->assertCount(1, $adminOrganisations);

        $this->assertEquals($organisations->get(1)->id, $adminOrganisations[0]->id);
    }

    public function test_organisation_scope_has_no_admin_invite_returns_only_organisations_with_no_admin_invites()
    {
        $organisations = factory(Organisation::class, 2)->create();
        factory(User::class)->create()->makeGlobalAdmin();

        factory(OrganisationAdminInvite::class)->states('email')->create([
            'organisation_id' => $organisations->get(0)->id,
        ]);

        $adminOrganisations = Organisation::select(table(Organisation::class, '*'))->hasNoAdminInvite()->get();

        $this->assertCount(1, $adminOrganisations);

        $this->assertEquals($organisations->get(1)->id, $adminOrganisations[0]->id);
    }

    public function test_organisation_scope_has_no_pending_admin_invite_returns_only_organisations_with_no_pending_admin_invites()
    {
        $organisations = factory(Organisation::class, 2)->create();
        factory(User::class)->create()->makeGlobalAdmin();

        factory(PendingOrganisationAdmin::class)->create([
            'organisation_id' => $organisations->get(0)->id,
        ]);

        $adminOrganisations = Organisation::select(table(Organisation::class, '*'))->hasNoPendingAdminInvite()->get();

        $this->assertCount(1, $adminOrganisations);

        $this->assertEquals($organisations->get(1)->id, $adminOrganisations[0]->id);
    }
}
