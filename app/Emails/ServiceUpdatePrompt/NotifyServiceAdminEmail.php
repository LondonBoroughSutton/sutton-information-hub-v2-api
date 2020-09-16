<?php

namespace App\Emails\ServiceUpdatePrompt;

use App\Emails\Email;

class NotifyServiceAdminEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('hlp.notifications_template_ids.service_update_prompt.notify_service_admin.email');
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return <<<'EOT'
Hello,

This is a reminder that your page, ((SERVICE_NAME)) on Connected Together has not been updated in over 6 months.

View the page on Connected Together:
((SERVICE_URL))

Update Page
You can login to our backend portal to update the page by entering your details and clicking the ‘Services’ tab. If you can’t remember your login, or need some additional support, feel free to contact the support team.

Access the NHS Connect admim portal to update:
https://api.connect.nhs.uk/login

Page doesn’t need updating?
Let us know:
((SERVICE_STILL_UP_TO_DATE_URL))

We’ll make a note that the page is up to date already.

Service no longer running?
Please let us know if you’d like the page removed from the site. A member of our admin team will disable it for you.

Contact us by email: hlp.admin.connect@nhs.net

Don’t think you should have received this?
You have received this because you are one of the admins for this page. If you believe this is incorrect, please let us know. We’ll be happy to change your permissions.

Contact us by email: hlp.admin.connect@nhs.net

Many thanks,

NHS Connect Team 
EOT;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return '((SERVICE_NAME)) page on Connected Together';
    }
}
