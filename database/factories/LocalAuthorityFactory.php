<?php

use App\Models\LocalAuthority;
use Faker\Generator as Faker;

$factory->define(LocalAuthority::class, function (Faker $faker) {
    $faker = \Faker\Factory::create('en_GB');

    return [
        'name' => $faker->city,
        'code' => $faker->numerify('E060000##'),
    ];
});

$factory->state(LocalAuthority::class, 'alt_name', function (Faker $faker) {
    return [
        'alt_name' => '',
    ];
});

$factory->state(LocalAuthority::class, 'scottish', function (Faker $faker) {
    return [
        'code' => $faker->numerify('S060000##'),
    ];
});

$factory->state(LocalAuthority::class, 'welsh', function (Faker $faker) {
    return [
        'code' => $faker->numerify('W060000##'),
    ];
});

$factory->state(LocalAuthority::class, 'n_irish', function (Faker $faker) {
    return [
        'code' => $faker->numerify('N060000##'),
    ];
});
