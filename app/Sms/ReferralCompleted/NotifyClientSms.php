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
        return config('gov_uk_notify.notifications_template_ids.referral_completed.notify_client.sms');
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return 'sms.referral.completed.notify_client';
    }
}
