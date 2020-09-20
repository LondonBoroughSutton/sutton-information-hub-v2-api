<?php

namespace App\Http\Requests\OrganisationAdminInvite;

use App\Rules\Password;
use App\Rules\UkPhoneNumber;
use App\Rules\UserEmailNotTaken;
use Illuminate\Foundation\Http\FormRequest;

class SubmitRequest extends FormRequest
{
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
        return [
            'first_name' => ['required', 'string', 'min:1', 'max:255'],
            'last_name' => ['required', 'string', 'min:1', 'max:255'],
            'email' => ['required', 'email', 'max:255', new UserEmailNotTaken()],
            'phone' => ['present', 'nullable', 'string', 'min:1', 'max:255', new UkPhoneNumber()],
            'password' => ['required', 'string', 'min:8', 'max:255', new Password()],
        ];
    }
}
