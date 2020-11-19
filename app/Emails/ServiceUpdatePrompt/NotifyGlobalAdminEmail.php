<?php

namespace App\Emails\ServiceUpdatePrompt;

use App\Emails\Email;

class NotifyGlobalAdminEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('hlp.notifications_template_ids.service_update_prompt.notify_global_admin.email');
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return <<<'EOT'
((SERVICE_NAME)) on Connect has not been updated in over 12 months.

View the page on Connect:
((SERVICE_URL))

Reminders have been sent monthly to the following:
((SERVICE_ADMIN_NAMES))

Page already up to date?
Reset the clock:
((SERVICE_STILL_UP_TO_DATE_URL))

Disable page?
You can disable the page in the backend:
((SERVICE_URL))
EOT;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return '((SERVICE_NAME)) page on Connect – Inactive for 1 year';
    }
}
