<?php

namespace App\Sms\OrganisationAdminInviteFirstFollowUps;

use App\Sms\Sms;

class NotifyInviteeSms extends Sms
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('hlp.notifications_template_ids.organisation_admin_invite_first_follow_ups.notify_invitee.sms');
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return <<<'EOT'
I’m writing to invite you to claim a listing for ((ORGANISATION_NAME)) on NHS Connect. Please go to ((INVITE_URL)) to claim your listing. FAQs: https://connect.nhs.uk/providers
EOT;
    }
}
