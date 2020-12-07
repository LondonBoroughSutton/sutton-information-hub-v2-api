<?php

namespace App\Jobs;

use App\Emails\UserCreated\NotifyUserEmail;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyNewUser implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected $userId;

    /**
     * Create a new job instance.
     *
     * @param string $userId
     */
    public function __construct(string $userId)
    {
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        if ($user = User::find($this->userId)) {
            // User found, send the email
            $permissions = $user
                ->userRoles()
                ->with('role', 'organisation', 'service')
                ->get()
                ->map(function (UserRole $userRole) {
                    switch ($userRole->role_id) {
                        case Role::superAdmin()->id:
                            return 'Super admin';
                        case Role::globalAdmin()->id:
                            return 'Global admin';
                        case Role::organisationAdmin()->id:
                            return "Organisation admin for {$userRole->organisation->name}";
                        case Role::serviceAdmin()->id:
                            return "Service admin for {$userRole->service->name}";
                        case Role::serviceWorker()->id:
                            return "Service worker for {$userRole->service->name}";
                        default:
                            return 'Unknown role';
                    }
                });
            $permissions = $permissions->implode(', ');

            // Only send an email if email address was provided.
            $user->sendEmail(new NotifyUserEmail($user->email, [
                'NAME' => $user->first_name,
                'PERMISSIONS' => $permissions,
            ]));
        }
    }
}
