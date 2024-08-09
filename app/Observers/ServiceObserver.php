<?php

namespace App\Observers;

use App\Emails\StaleServiceDisabled\NotifyGlobalAdminEmail;
use App\Exceptions\CannotRevokeRoleException;
use App\Models\Notification;
use App\Models\Role;
use App\Models\Service;
use App\Models\UserRole;

class ServiceObserver
{
    /**
     * Handle the service "created" event.
     */
    public function created(Service $service): void
    {
        // Add service admin roles to all service->organisation->admins
        UserRole::query()
            ->with('user')
            ->where('role_id', Role::organisationAdmin()->id)
            ->where('organisation_id', $service->organisation_id)
            ->get()
            ->each(function (UserRole $userRole) use ($service) {
                $userRole->user->makeServiceAdmin($service);
            });
    }

    /**
     * Handle the service "updated" event.
     */
    public function updated(Service $service): void
    {
        // Check if the organisation_id was updated.
        if ($service->isDirty('organisation_id')) {
            // Remove old service workers and service admins.
            UserRole::query()
                ->with('user')
                ->where('service_id', $service->id)
                ->get()
                ->each(function (UserRole $userRole) use ($service) {
                    try {
                        $userRole->user->revokeServiceAdmin($service);
                    } catch (CannotRevokeRoleException $exception) {
                        // Do nothing.
                    }

                    try {
                        $userRole->user->revokeServiceWorker($service);
                    } catch (CannotRevokeRoleException $exception) {
                        // Do nothing.
                    }
                });

            // Add new service admins.
            UserRole::query()
                ->with('user')
                ->where('role_id', Role::organisationAdmin()->id)
                ->where('organisation_id', $service->organisation_id)
                ->get()
                ->each(function (UserRole $userRole) use ($service) {
                    $userRole->user->makeServiceAdmin($service);
                });
        }

        // Check if the status was updated.
        if ($service->isDirty('status')) {
            // Check if the service was disabled and last modified over a year ago.
            if (
                $service->status === Service::STATUS_INACTIVE
                && $service->getOriginal('last_modified_at')
            ) {
                Notification::sendEmail(
                    new NotifyGlobalAdminEmail(
                        config('local.global_admin.email'),
                        ['SERVICE_NAME' => $service->name]
                    )
                );
            }
        }
    }

    /**
     * Handle the service "deleting" event.
     */
    public function deleting(Service $service)
    {
        $service->updateRequests->each->delete();
        $service->userRoles->each->delete();
        $service->referrals->each->delete();
        $service->serviceLocations->each->delete();
        $service->socialMedias->each->delete();
        $service->usefulInfos->each->delete();
        $service->serviceGalleryItems->each->delete();
        $service->serviceTaxonomies->each->delete();
        $service->offerings->each->delete();
        $service->serviceRefreshTokens->each->delete();
    }
}
