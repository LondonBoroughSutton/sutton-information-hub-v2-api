<?php

namespace App\Generators;

use App\Models\OrganisationAdminInvite;
use App\Models\PendingOrganisationAdmin;

class AdminUrlGenerator
{
    /**
     * @var string
     */
    protected $adminUrl;

    /**
     * AdminUrlGenerator constructor.
     *
     * @param string $adminUrl
     */
    public function __construct(string $adminUrl)
    {
        $this->adminUrl = $adminUrl;
    }

    /**
     * @param \App\Models\OrganisationAdminInvite $organisationAdminInvite
     * @return string
     */
    public function generateOrganisationAdminInviteUrl(OrganisationAdminInvite $organisationAdminInvite): string
    {
        return "{$this->adminUrl}/organisation-admin-invites/{$organisationAdminInvite->id}";
    }

    /**
     * @param \App\Models\PendingOrganisationAdmin $pendingOrganisationAdmin
     * @return string
     */
    public function generatePendingOrganisationAdminConfirmationUrl(
        PendingOrganisationAdmin $pendingOrganisationAdmin
    ): string {
        return "{$this->adminUrl}/pending-organisation-admins/{$pendingOrganisationAdmin->id}/confirm";
    }
}
