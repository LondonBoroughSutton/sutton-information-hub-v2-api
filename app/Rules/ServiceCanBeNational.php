<?php

namespace App\Rules;

use App\Models\Service;
use Illuminate\Contracts\Validation\Rule;

class ServiceCanBeNational implements Rule
{
    /**
     * Service ID.
     *
     * @var string
     */
    protected $service_id;

    /**
     * Create a new rule instance.
     *
     * @param \App\Models\User $user
     * @param mixed|null $service_id
     */
    public function __construct($service_id = null)
    {
        $this->service_id = $service_id;
    }

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
            'id' => $this->service_id,
        ])->whereNotExists(function ($query) {
            $query->select(\DB::raw(1))
                ->from('service_locations')
                ->whereRaw('service_locations.service_id = services.id');
        })->exists();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The Support listing has Locations.';
    }
}
