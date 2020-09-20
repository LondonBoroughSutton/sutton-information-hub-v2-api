<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command(Commands\Hlp\Notify\StaleServicesCommand::class)
            ->monthlyOn(15, '09:00');

        $schedule->command(Commands\Hlp\Notify\UnactionedReferralsCommand::class)
            ->dailyAt('09:00');

        $schedule->command(Commands\Hlp\Notify\StillUnactionedReferralsCommand::class)
            ->dailyAt('09:00');

        $schedule->command(Commands\Hlp\AutoDelete\AuditsCommand::class)
            ->daily();

        $schedule->command(Commands\Hlp\AutoDelete\PageFeedbacksCommand::class)
            ->daily();

        $schedule->command(Commands\Hlp\AutoDelete\PendingAssignmentFilesCommand::class)
            ->daily();

        $schedule->command(Commands\Hlp\AutoDelete\ReferralsCommand::class)
            ->daily();

        $schedule->command(Commands\Hlp\AutoDelete\ServiceRefreshTokensCommand::class)
            ->daily();

        $schedule->command(Commands\Hlp\Notify\OrganisationAdminInvitee\FirstFollowUpsCommand::class)
            ->dailyAt('13:30');

        $schedule->command(Commands\Hlp\Notify\OrganisationAdminInvitee\SecondFollowUpsCommand::class)
            ->dailyAt('13:30');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
