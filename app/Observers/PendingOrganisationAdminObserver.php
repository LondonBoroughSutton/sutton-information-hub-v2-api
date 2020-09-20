<?php

namespace App\Observers;

use App\Emails\PendingOrganisationAdminConfirmation\NotifyPendingOrganisationAdminEmail;
use App\Generators\AdminUrlGenerator;
use App\Models\Notification;
use App\Models\PendingOrganisationAdmin;

class PendingOrganisationAdminObserver
{
    /**
     * @var \App\Generators\AdminUrlGenerator
     */
    protected $adminUrlGenerator;

    /**
     * PendingOrganisationAdminObserver constructor.
     *
     * @param \App\Generators\AdminUrlGenerator $adminUrlGenerator
     */
    public function __construct(AdminUrlGenerator $adminUrlGenerator)
    {
        $this->adminUrlGenerator = $adminUrlGenerator;
    }

    /**
     * Handle the pending organisation admin "created" event.
     *
     * @param \App\Models\PendingOrganisationAdmin $pendingOrganisationAdmin
     */
    public function created(PendingOrganisationAdmin $pendingOrganisationAdmin)
    {
        // Send notification to the pending organisation admin.
        Notification::sendEmail(new NotifyPendingOrganisationAdminEmail(
            $pendingOrganisationAdmin->email,
            [
                'ORGANISATION_NAME' => $pendingOrganisationAdmin->organisation->name,
                'CONFIRM_EMAIL_URL' => $this->adminUrlGenerator->generatePendingOrganisationAdminConfirmationUrl(
                    $pendingOrganisationAdmin
                ),
            ]
        ));
    }
}
