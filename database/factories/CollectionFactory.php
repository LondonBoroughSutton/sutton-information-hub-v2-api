<?php

use App\Models\Collection;
use Faker\Generator as Faker;

$factory->define(Collection::class, function (Faker $faker) {
    return [
        'type' => Collection::TYPE_CATEGORY,
        'name' => $faker->sentence(2),
        'meta' => [
            'intro' => $faker->sentence,
            'subtitle' => $faker->sentence,
            'sideboxes' => [],
            'image_file_id' => null,
        ],
        'order' => $faker->numberBetween(1, 5),
        'enabled' => true,
    ];
});

$factory->state(Collection::class, 'typePersona', [
    'type' => Collection::TYPE_PERSONA,
]);
