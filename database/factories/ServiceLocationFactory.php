<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceLocationFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'service_id' => function () {
                return \App\Models\Service::factory()->create()->id;
            },
            'location_id' => function () {
                return \App\Models\Location::factory()->create()->id;
            },
        ];
    }
}
