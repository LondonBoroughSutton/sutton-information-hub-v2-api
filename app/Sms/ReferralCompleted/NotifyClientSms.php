<?php

namespace App\Sms\ReferralCompleted;

use App\Sms\Sms;

class NotifyClientSms extends Sms
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('hlp.notifications_template_ids.referral_completed.notify_client.sms');
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return <<<'EOT'
Connected Together: You've made a connection on Connected Together ((REFERRAL_ID)). The service should contact you within 10 working days. Any feedback contact info@connectedtogether.org.uk
EOT;
    }
}
