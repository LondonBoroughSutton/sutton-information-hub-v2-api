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
        return config('gov_uk_notify.notifications_template_ids.referral_created.notify_client.sms');
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return 'sms.referral.created.notify_client';
    }
}
