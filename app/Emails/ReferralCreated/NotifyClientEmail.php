<?php

namespace App\Emails\ReferralCreated;

use App\Emails\Email;

class NotifyClientEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('hlp.notifications_template_ids.referral_created.notify_client.email');
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return <<<'EOT'
Hello,

You’ve successfully connected to ((REFERRAL_SERVICE_NAME))!

They should be in touch with you via ((REFERRAL_CONTACT_METHOD)) within 10 working days.

Your referral ID is ((REFERRAL_ID)).

If you have any feedback regarding this connection, or have not heard back within 10 working days, please contact the admin team via hlp.admin.connect@nhs.net.

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
