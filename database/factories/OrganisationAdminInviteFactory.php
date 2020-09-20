<?php

use App\Models\Organisation;
use App\Models\OrganisationAdminInvite;
use Faker\Generator as Faker;

$factory->define(OrganisationAdminInvite::class, function (Faker $faker) {
    return [
        'organisation_id' => function () {
            return factory(Organisation::class)->create()->id;
        },
        'email' => null,
    ];
});

$factory->state(OrganisationAdminInvite::class, 'email', function (Faker $faker) {
    return [
        'email' => $faker->safeEmail,
    ];
});
