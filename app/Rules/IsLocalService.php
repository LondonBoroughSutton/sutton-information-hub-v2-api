<?php

namespace App\Rules;

use App\Models\Service;
use Illuminate\Contracts\Validation\Rule;

class IsLocalService implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return Service::query()->where([
            ['id', '=', $value],
            ['is_national', '<>', true],
        ])->exists();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The Support listing is a National Support listing.';
    }
}
