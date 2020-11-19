<?php

namespace App\Emails\ReferralCompleted;

use App\Emails\Email;

class NotifyClientEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('hlp.notifications_template_ids.referral_completed.notify_client.email');
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return <<<'EOT'
Hello,

Your referral ID is ((REFERRAL_ID)).

Your connection to ((SERVICE_NAME)) has been marked as completed by the support listing.

This means that they have been in touch with you about accessing their support listing.

If you have any feedback regarding this connection or believe the support listing did not try to contact you, please contact the admin team via hlp.admin.connect@nhs.net.

Many thanks,

NHS Connect Team
EOT;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return 'Confirmation of referral';
    }
}
