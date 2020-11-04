<?php

namespace App\Http\Controllers\Core\V1\PendingOrganisationAdmin;

use App\Events\EndpointHit;
use App\Http\Controllers\Controller;
use App\Http\Requests\PendingOrganisationAdmin\ConfirmRequest;
use App\Http\Resources\UserResource;
use App\Models\PendingOrganisationAdmin;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use App\RoleManagement\RoleManagerInterface;
use Illuminate\Support\Facades\DB;

class ConfirmController extends Controller
{
    /**
     * @param \App\Models\PendingOrganisationAdmin $pendingOrganisationAdmin
     * @param \App\Http\Requests\PendingOrganisationAdmin\ConfirmRequest $request
     * @return mixed
     */
    public function store(PendingOrganisationAdmin $pendingOrganisationAdmin, ConfirmRequest $request)
    {
        return DB::transaction(function () use ($pendingOrganisationAdmin, $request) {
            $user = User::create([
                'first_name' => $pendingOrganisationAdmin->first_name,
                'last_name' => $pendingOrganisationAdmin->last_name,
                'email' => $pendingOrganisationAdmin->email,
                'phone' => $pendingOrganisationAdmin->phone,
                'password' => $pendingOrganisationAdmin->password,
            ]);
            /** @var \App\RoleManagement\RoleManagerInterface $roleManager */
            $roleManager = app()->make(RoleManagerInterface::class, [
                'user' => $user,
            ]);

            // Update the user roles.
            $roleManager->updateRoles(array_merge($user->userRoles->all(), [
                new UserRole([
                    'role_id' => Role::organisationAdmin()->id,
                    'organisation_id' => $pendingOrganisationAdmin->organisation->id,
                ]),
            ]));

            $pendingOrganisationAdmin->delete();

            event(EndpointHit::onCreate(
                $request,
                "Confirmed pending organisation admin email and created user [{$user->id}]",
                $user
            ));

            return new UserResource($user->load('userRoles'));
        });
    }
}
