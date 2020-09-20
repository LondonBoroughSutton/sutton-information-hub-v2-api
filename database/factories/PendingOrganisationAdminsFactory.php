<?php

use App\Models\Organisation;
use App\Models\PendingOrganisationAdmin;
use Faker\Generator as Faker;

$factory->define(PendingOrganisationAdmin::class, function (Faker $faker) {
    return [
        'organisation_id' => function () {
            return factory(Organisation::class)->create()->id;
        },
        'first_name' => $faker->firstName,
        'last_name' => $faker->lastName,
        'email' => $faker->safeEmail,
        'phone' => null,
        'password' => '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', // secret
    ];
});
