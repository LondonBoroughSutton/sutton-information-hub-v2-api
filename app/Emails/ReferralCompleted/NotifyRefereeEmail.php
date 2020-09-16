<?php

namespace App\Emails\ReferralCompleted;

use App\Emails\Email;

class NotifyRefereeEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('hlp.notifications_template_ids.referral_completed.notify_referee.email');
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return <<<'EOT'
Hi ((REFEREE_NAME)),

The referral you made to ((SERVICE_NAME)) has been marked as complete. Referral ID: ((REFERRAL_ID)).

Your client should have been contacted by now, but if they haven’t then please contact them on ((SERVICE_PHONE)) or by email at ((SERVICE_EMAIL)).

If you would like to leave any feedback on the referral or get in touch with us, you can contact us at hlp.admin.connect@nhs.net.

Many thanks,

NHS Connect Team 
EOT;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return 'Referral Completed';
    }
}
