<?php

namespace App\Emails\StaleServiceDisabled;

use App\Emails\Email;

class NotifyGlobalAdminEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('hlp.notifications_template_ids.stale_service_disabled.notify_global_admin.email');
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return <<<'EOT'
((SERVICE_NAME)) on Connect has been marked as disabled after not being updated for over a year.
EOT;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return 'Disabled ((SERVICE_NAME)) page on Connect';
    }
}
