<?php

namespace App\Models\Mutators;

use App\Models\OrganisationAdminInvite;
use App\Models\PendingOrganisationAdmin;
use App\Models\Role;
use App\Models\UserRole;
use Illuminate\Support\Facades\DB;

trait OrganisationMutators
{
    /**
     * Calculate the Admin Invite status of the Organisation.
     *
     * @return string
     */
    public function getAdminInviteStatusAttribute()
    {
        $organisationAdminExists = DB::table(table(UserRole::class))
            ->where([
                'organisation_id' => $this->id,
                'role_id' => Role::organisationAdmin()->id,
            ])
            ->whereNotIn('user_id', Role::globalAdmin()->users()->pluck('users.id'))
            ->exists();

        if ($organisationAdminExists) {
            return self::ADMIN_INVITE_STATUS_CONFIRMED;
        }

        $pendingOrganisationAdminExists = DB::table(table(PendingOrganisationAdmin::class))->where('organisation_id', $this->id)->exists();

        if ($pendingOrganisationAdminExists) {
            return self::ADMIN_INVITE_STATUS_PENDING;
        }

        $organisationAdminInviteExists = DB::table(table(OrganisationAdminInvite::class))->where('organisation_id', $this->id)->exists();

        if ($organisationAdminInviteExists) {
            return self::ADMIN_INVITE_STATUS_INVITED;
        }

        return self::ADMIN_INVITE_STATUS_NONE;
    }
}
