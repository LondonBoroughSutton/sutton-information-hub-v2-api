<?php

namespace App\Emails\ReferralCreated;

use App\Emails\Email;

class NotifyRefereeEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('hlp.notifications_template_ids.referral_created.notify_referee.email');
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return <<<'EOT'
Hello ((REFEREE_NAME)),

You’ve successfully made a referral to ((REFERRAL_SERVICE_NAME))!

They should be in touch with the client by ((REFERRAL_CONTACT_METHOD)) to speak to them about accessing the support listing within 10 working days.

The referral ID is ((REFERRAL_ID)). If you have any feedback regarding this connection, please contact the admin team via hlp.admin.connect@nhs.net.

Many thanks,

NHS Connect Team
EOT;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return 'Confirmation of referral';
    }
}
