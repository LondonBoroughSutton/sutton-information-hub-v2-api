<?php

use App\Models\User;
use Faker\Generator as Faker;

$factory->define(User::class, function (Faker $faker) {
    return [
        'first_name' => $faker->firstName,
        'last_name' => $faker->lastName,
        'email' => $faker->unique()->safeEmail,
        'phone' => random_uk_phone(),
        'password' => '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', // secret
    ];
});

$factory->state(User::class, 'employed', function (Faker $faker) {
    return [
        'employer_name' => $faker->company,
    ];
});

$factory->state(User::class, 'address', function (Faker $faker) {
    return [
        'location_id' => function () {
            return factory(\App\Models\Location::class)->create()->id;
        },
    ];
});

$factory->state(User::class, 'localAuthority', function (Faker $faker) {
    return [
        'local_authority_id' => function () {
            return factory(\App\Models\LocalAuthority::class)->create()->id;
        },
    ];
});
