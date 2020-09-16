<?php

namespace App\Emails\OrganisationSignUpFormApproved;

use App\Emails\Email;

class NotifySubmitterEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('hlp.notifications_template_ids.organisation_sign_up_form_approved.notify_submitter.email');
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return <<<'EOT'
((ORGANISATION_NAME)) is on Connected Together!
Hi ((SUBMITTER_NAME)),

Your request to register ((ORGANISATION_NAME)) on Connected Together on ((REQUEST_DATE)) has been approved.

Your service may not be visible on the site immediately due to the time it takes for our administration team to process new organisations.

If you have any questions, please contact us at hlp.admin.connect@nhs.net.

Many thanks,

NHS Connect Team 

You can now log on to the administration portal to update your page or add new services
You will find more options to customise your page than were available on the completed form. You can access the administration portal at: https://admin.connect.nhs.uk
EOT;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return 'Organisation Sign Up Form Approved';
    }
}
