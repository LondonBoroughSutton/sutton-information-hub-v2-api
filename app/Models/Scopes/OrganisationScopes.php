<?php

namespace App\Models\Scopes;

use App\Models\Organisation;
use App\Models\OrganisationAdminInvite;
use App\Models\PendingOrganisationAdmin;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;

trait OrganisationScopes
{
    /**
     * Scope to only include Organisations with a non Global Admin.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHasAdmin($query)
    {
        return $query->join(table(UserRole::class), table(UserRole::class, 'organisation_id'), '=', table(Organisation::class, 'id'))
            ->where(table(UserRole::class, 'role_id'), '=', Role::organisationAdmin()->id)
            ->whereNotIn(table(UserRole::class, 'user_id'), Role::globalAdmin()->users()->pluck(table(User::class, 'id')));
    }

    /**
     * Scope to only include Organisations without an Admin or any Invites.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHasNoAdmin($query)
    {
        $organisationAdmins = UserRole::select('*')
            ->where(table(UserRole::class, 'role_id'), '=', Role::organisationAdmin()->id)
            ->whereNotIn(table(UserRole::class, 'user_id'), Role::globalAdmin()->users()->pluck(table(User::class, 'id')));

        return $query->leftJoinSub($organisationAdmins, 'organisation_admins', function ($join) {
            $join->on(table(Organisation::class, 'id'), '=', 'organisation_admins.organisation_id');
        })->whereNull('organisation_admins.organisation_id');
    }

    /**
     * Scope to only include Organisations with an open Admin Invite.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHasAdminInvite($query)
    {
        return $query->join(table(OrganisationAdminInvite::class), table(Organisation::class, 'id'), '=', table(OrganisationAdminInvite::class, 'organisation_id'));
    }

    /**
     * Scope to only include Organisations without an open Admin Invite.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHasNoAdminInvite($query)
    {
        return $query->leftJoin(table(OrganisationAdminInvite::class), table(Organisation::class, 'id'), '=', table(OrganisationAdminInvite::class, 'organisation_id'))->whereNull(table(OrganisationAdminInvite::class, 'organisation_id'));
    }

    /**
     * Scope to only include Organisations with an pending Admin Invite.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHasPendingAdminInvite($query)
    {
        return $query->join(table(PendingOrganisationAdmin::class), table(Organisation::class, 'id'), '=', table(PendingOrganisationAdmin::class, 'organisation_id'));
    }

    /**
     * Scope to only include Organisations without an pending Admin Invite.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHasNoPendingAdminInvite($query)
    {
        return $query->leftJoin(table(PendingOrganisationAdmin::class), table(Organisation::class, 'id'), '=', table(PendingOrganisationAdmin::class, 'organisation_id'))
            ->whereNull(table(PendingOrganisationAdmin::class, 'organisation_id'));
    }
}
