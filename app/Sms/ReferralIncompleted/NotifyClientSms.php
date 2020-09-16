<?php

namespace App\Sms\ReferralIncompleted;

use App\Sms\Sms;

class NotifyClientSms extends Sms
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('hlp.notifications_template_ids.referral_incompleted.notify_client.sms');
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return <<<'EOT'
Connected Together: Hi ((CLIENT_INITIALS)),

Your referral (ID: ((REFERRAL_ID))) has been marked as incomplete. This means the service tried to contact you but couldn't.

For details: hlp.admin.connect@nhs.net

NHS Connect Team 
EOT;
    }
}
