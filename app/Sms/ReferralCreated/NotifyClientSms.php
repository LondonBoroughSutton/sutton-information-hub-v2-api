<?php

namespace App\Sms\ReferralCreated;

use App\Sms\Sms;

class NotifyClientSms extends Sms
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('hlp.notifications_template_ids.referral_created.notify_client.sms');
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return <<<'EOT'
Connect: You've made a connection on Connect ((REFERRAL_ID)). The service should contact you within 10 working days. Any feedback contact hlp.admin.connect@nhs.net
EOT;
    }
}
