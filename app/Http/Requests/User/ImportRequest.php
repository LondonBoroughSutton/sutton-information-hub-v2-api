<?php

namespace App\Http\Requests\User;

use App\Models\Role;
use App\RoleManagement\RoleAuthorizerInterface;
use App\Rules\CanAssignRoleToUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportRequest extends FormRequest
{
    use UserRoleHelpers;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if ($this->user()) {
            return $this->user()->isSuperAdmin();
        }

        return false;
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
            'spreadsheet' => [
                'required',
                'regex:/^data:application\/[a-z\-\.]+;base64,/',
            ],
            'local_authority_id' => [
                'nullable',
                Rule::requiredIf(in_array(Role::NAME_LOCAL_ADMIN, $this->input('roles.*.role', []))),
                'exists:local_authorities,id',
            ],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['sometimes', 'array', $canAssignRoleToUserRule],
            'roles.*.role' => ['required_with:roles.*', 'string', 'exists:roles,name'],
            'roles.*.organisation_id' => [
                'nullable',
                Rule::requiredIf(in_array(Role::NAME_ORGANISATION_ADMIN, $this->input('roles.*.role', []))),
                'exists:organisations,id',
            ],
            'roles.*.service_id' => [
                'nullable',
                Rule::requiredIf(in_array(Role::NAME_SERVICE_WORKER, $this->input('roles.*.role', []))),
                Rule::requiredIf(in_array(Role::NAME_SERVICE_ADMIN, $this->input('roles.*.role', []))),
                'exists:services,id',
            ],
        ];
    }
}
