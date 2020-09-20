<?php

namespace Tests\Unit\Generators;

use App\Generators\AdminUrlGenerator;
use App\Models\OrganisationAdminInvite;
use App\Models\PendingOrganisationAdmin;
use Tests\TestCase;

class AdminUrlGeneratorTest extends TestCase
{
    public function test_generateOrganisationAdminInviteUrl_works()
    {
        $organisationAdminInvite = new OrganisationAdminInvite([
            'id' => 'test-id',
        ]);

        $generator = new AdminUrlGenerator('http://example.com');
        $url = $generator->generateOrganisationAdminInviteUrl($organisationAdminInvite);

        $this->assertEquals('http://example.com/organisation-admin-invites/test-id', $url);
    }

    public function test_generatePendingOrganisationAdminConfirmationUrl_works()
    {
        $pendingOrganisationAdmin = new PendingOrganisationAdmin([
            'id' => 'test-id',
        ]);

        $generator = new AdminUrlGenerator('http://example.com');
        $url = $generator->generatePendingOrganisationAdminConfirmationUrl(
            $pendingOrganisationAdmin
        );

        $this->assertEquals('http://example.com/pending-organisation-admins/test-id/confirm', $url);
    }
}
