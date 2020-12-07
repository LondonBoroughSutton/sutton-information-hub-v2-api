<?php

namespace App\Emails\ReferralUnactioned;

use App\Emails\Email;

class NotifyServiceEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('hlp.notifications_template_ids.referral_unactioned.notify_service.email');
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return 'Pending to be sent. Content will be filled once sent.';
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return <<<'EOT'
Hello,

You received a referral to your support listing ((REFERRAL_SERVICE_NAME)) for ((REFERRAL_INITIALS)) and ((REFERRAL_ID)) ((REFERRAL_DAYS_AGO)) working days ago.

This is a ((REFERRAL_TYPE)).

Please contact the client via ((REFERRAL_CONTACT_METHOD)) within the next ((REFERRAL_DAYS_LEFT)) working days.

If you are unable to get in contact with the client, you can mark the referral is ‘Incomplete’.

You can update the status of the referral in the admin portal:
http://admin.connect.nhs.uk/referrals

If you have any questions, please contact us at hlp.admin.connect@nhs.net.

Many thanks,

NHS Connect Team
EOT;
    }
}
