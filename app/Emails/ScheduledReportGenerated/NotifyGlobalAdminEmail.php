<?php

namespace App\Emails\ScheduledReportGenerated;

use App\Emails\Email;

class NotifyGlobalAdminEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('hlp.notifications_template_ids.scheduled_report_generated.notify_global_admin.email');
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return <<<'EOT'
Hello,

A ((REPORT_FREQUENCY)) ((REPORT_TYPE)) report has been generated.

Please login to the admin system to view the report.
EOT;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return 'Scheduled report generated';
    }
}
