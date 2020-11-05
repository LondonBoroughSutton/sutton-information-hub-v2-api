<?php

namespace App\Http\Requests\OrganisationAdminInvite;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if ($this->user()->isSuperAdmin() || $this->user()->isLocalAdmin()) {
            return true;
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
        return [
            'organisations' => ['present', 'array', function ($attribute, $value, $fail) {
                if ($this->user()->isLocalAdmin() && count($value) > 1) {
                    $fail('You may only invite one organisation at a time');
                }
            }],
            'organisations.*' => ['array'],
            'organisations.*.organisation_id' => ['required_with:organisations.*', 'exists:organisations,id'],
            'organisations.*.use_email' => ['required_with:organisations.*', 'boolean'],
        ];
    }
}
