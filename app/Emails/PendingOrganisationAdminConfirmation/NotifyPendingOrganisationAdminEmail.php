<?php

namespace App\Emails\PendingOrganisationAdminConfirmation;

use App\Emails\Email;

class NotifyPendingOrganisationAdminEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('hlp.notifications_template_ids.pending_organisation_admin_confirmation.notify_pending_organisation_admin.email');
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return <<<'EOT'
Dear ((ORGANISATION_NAME)),
In order to complete the process, please click the confirmation link.
You then will be able to review and change your organisation’s details. 

Confirm your email:
((CONFIRM_EMAIL_URL))

Many thanks,
NHS Connect Team

EOT;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return 'Please confirm your email address';
    }
}
