<?php

namespace App\Sms\ReferralCreated;

use App\Sms\Sms;

class NotifyRefereeSms extends Sms
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('hlp.notifications_template_ids.referral_created.notify_referee.sms');
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return <<<'EOT'
Connect: You've made a connection for a client on Connect ((REFERRAL_ID)). The support listing should contact them within 10 working days. Any feedback contact hlp.admin.connect@nhs.net
EOT;
    }
}
