<?php

namespace App\Models\Mutators;

trait OrganisationMutators
{
    /**
     * Calculate the Admin Invite status of the Organisation.
     *
     * @return string
     */
    public function getAdminInviteStatusAttribute()
    {
        /**
         * Is there a specific Organisation Admin.
         */
        if (self::hasAdmin()->where(table(self::class, 'id'), $this->id)->exists()) {
            return self::ADMIN_INVITE_STATUS_CONFIRMED;
        }

        /**
         * Is there a pending Admin Invite.
         */
        if (self::hasPendingAdminInvite()->where(table(self::class, 'id'), $this->id)->exists()) {
            return self::ADMIN_INVITE_STATUS_PENDING;
        }

        /**
         * Is there an Admin Invite.
         */
        if (self::hasAdminInvite()->where(table(self::class, 'id'), $this->id)->exists()) {
            return self::ADMIN_INVITE_STATUS_INVITED;
        }

        /**
         * There are no Admins or Invites.
         */
        return self::ADMIN_INVITE_STATUS_NONE;
    }
}
