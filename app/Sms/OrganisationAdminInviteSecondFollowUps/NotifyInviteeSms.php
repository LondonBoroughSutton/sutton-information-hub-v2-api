<?php

namespace App\Sms\OrganisationAdminInviteSecondFollowUps;

use App\Sms\Sms;

class NotifyInviteeSms extends Sms
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('hlp.notifications_template_ids.organisation_admin_invite_second_follow_ups.notify_invitee.sms');
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return <<<'EOT'
We contacted you five weeks ago to invite you to claim a listing for ((ORGANISATION_NAME)) on NHS Connect. Please go to ((INVITE_URL)) to claim your listing. FAQs: https://connect.nhs.uk/providers
EOT;
    }
}
