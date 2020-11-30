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
            'roles.*' => [
                'sometimes',
                'array',
                $canAssignRoleToUserRule,
                function ($attribute, $value, $fail) {
                    if (Role::NAME_ORGANISATION_ADMIN === $value['role'] && empty($value['organisation_id'])) {
                        $fail('Organisation ID is required for ' . $value['role']);
                    }
                    if ((Role::NAME_SERVICE_WORKER === $value['role'] || Role::NAME_SERVICE_ADMIN === $value['role']) && empty($value['service_id'])) {
                        $fail('Service ID is required for ' . $value['role']);
                    }
                },
            ],
            'roles.*.role' => ['required_with:roles.*', 'string', 'exists:roles,name'],
            'roles.*.organisation_id' => [
                'sometimes',
                'exists:organisations,id',
                'nullable',
            ],
            'roles.*.service_id' => [
                'sometimes',
                'exists:services,id',
                'nullable',
            ],
        ];
    }
}
