<?php

use App\Models\File;
use App\Models\Location;
use App\Models\Organisation;
use App\Models\SocialMedia;
use Faker\Generator as Faker;
use Illuminate\Support\Str;

$factory->define(Organisation::class, function (Faker $faker) {
    $name = $faker->unique()->company;

    return [
        'slug' => Str::slug($name) . '-' . mt_rand(1, 1000),
        'name' => $name,
        'description' => 'This organisation provides x service.',
    ];
});

$factory->state(Organisation::class, 'web', function (Faker $faker) {
    return [
        'url' => $faker->url,
    ];
});

$factory->state(Organisation::class, 'email', function (Faker $faker) {
    return [
        'email' => $faker->safeEmail,
    ];
});

$factory->state(Organisation::class, 'phone', function (Faker $faker) {
    return [
        'phone' => random_uk_phone(),
    ];
});

$factory->state(Organisation::class, 'location', function (Faker $faker) {
    return [
        'location_id' => function () {
            return factory(Location::class)->create()->id;
        },
    ];
});

$factory->state(Organisation::class, 'logo', function (Faker $faker) {
    return [
        'logo_file_id' => function () {
            return factory(File::class)->create()->id;
        },
    ];
});

$factory->state(Organisation::class, 'social', []);
$factory->afterCreatingState(Organisation::class, 'social', function (Organisation $organisation, Faker $faker) {
    $organisation->socialMedias()->saveMany([
        factory(SocialMedia::class)->create(),
        factory(SocialMedia::class)->states('twitter')->create(),
        factory(SocialMedia::class)->states('instagram')->create(),
    ]);
});
