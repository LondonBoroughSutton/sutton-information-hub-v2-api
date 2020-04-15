<?php

namespace App\Emails\OrganisationSignUpFormRejected;

use App\Emails\Email;

class NotifySubmitterEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('hlp.notifications_template_ids.organisation_sign_up_form_rejected.notify_submitter.email');
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return <<<'EOT'
Hi ((SUBMITTER_NAME)),

Thank you for submitting your request to have ((ORGANISATION_NAME)) listed on Connected Together.

Unfortunately, your request to list ((ORGANISATION_NAME)) on Connected Together on ((REQUEST_DATE)) has been rejected. This is due to the organisation/service not meeting the terms and conditions of being listed on Connected Together.

You can read more about our terms and conditions: https://www.connectedtogether.org.uk/terms-and-conditions

If you have any questions, please contact us at info@connectedtogether.org.uk.

Many thanks,

The Connected Together team
EOT;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return 'Connected Together – New Organisation not approved';
    }
}
