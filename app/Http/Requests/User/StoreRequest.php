<?php

namespace App\Http\Requests\User;

use App\Models\Role;
use App\Models\UserRole;
use App\RoleManagement\RoleAuthorizerInterface;
use App\Rules\CanAssignRoleToUser;
use App\Rules\Password;
use App\Rules\UkPhoneNumber;
use App\Rules\UserEmailNotTaken;
use App\Rules\UserHasRole;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    use UserRoleHelpers;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $canAssignRoleToUserRule = new CanAssignRoleToUser(
            app()->make(RoleAuthorizerInterface::class, [
                'invokingUserRoles' => $this->user('api')->userRoles()->get()->all(),
            ])
        );

        return [
            'first_name' => ['required', 'string', 'min:1', 'max:255'],
            'last_name' => ['required', 'string', 'min:1', 'max:255'],
            'email' => ['required', 'email', 'max:255', new UserEmailNotTaken()],
            'phone' => ['present', 'nullable', 'string', 'min:1', 'max:255', new UkPhoneNumber()],
            'password' => ['required', 'string', 'min:8', 'max:255', new Password()],
            'employer_name' => [
                'sometimes',
                'nullable',
                'string',
                'min:1',
                'max:255',
                new UserHasRole(
                    $this->user('api'),
                    new UserRole([
                        'user_id' => $this->user('api')->id,
                        'role_id' => Role::superAdmin()->id,
                    ]),
                    null
                ),
            ],
            'local_authority_id' => [
                'sometimes',
                'nullable',
                'exists:local_authorities,id',
                new UserHasRole(
                    $this->user('api'),
                    new UserRole([
                        'user_id' => $this->user('api')->id,
                        'role_id' => Role::superAdmin()->id,
                    ]),
                    null
                ),
            ],
            'location_id' => [
                'sometimes',
                'nullable',
                'exists:locations,id',
                new UserHasRole(
                    $this->user('api'),
                    new UserRole([
                        'user_id' => $this->user('api')->id,
                        'role_id' => Role::superAdmin()->id,
                    ]),
                    null
                ),
            ],

            'roles' => ['required', 'array'],
            'roles.*' => ['required', 'array', $canAssignRoleToUserRule],
            'roles.*.role' => ['required_with:roles.*', 'string', 'exists:roles,name'],
            'roles.*.organisation_id' => [
                'required_if:roles.*.role,' . Role::NAME_ORGANISATION_ADMIN,
                'exists:organisations,id',
            ],
            'roles.*.service_id' => [
                'required_if:roles.*.role,' . Role::NAME_SERVICE_WORKER,
                'required_if:roles.*.role,' . Role::NAME_SERVICE_ADMIN,
                'exists:services,id',
            ],
        ];
    }
}
