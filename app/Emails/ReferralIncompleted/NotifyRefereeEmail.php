<?php

namespace App\Emails\ReferralIncompleted;

use App\Emails\Email;

class NotifyRefereeEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('hlp.notifications_template_ids.referral_incompleted.notify_referee.email');
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return <<<'EOT'
Hi ((REFEREE_NAME)),

The referral you made to ((SERVICE_NAME)) has been marked as incomplete with the following message:

“((REFERRAL_STATUS))“.

If you believe the support listing did not try to contact the client, or you have any other feedback regarding the connection, please contact us at hlp.admin.connect@nhs.net.

Many thanks,

NHS Connect Team
EOT;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return 'Referral Incompleted';
    }
}
