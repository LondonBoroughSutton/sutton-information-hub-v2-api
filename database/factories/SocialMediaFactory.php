<?php

use App\Models\Organisation;
use App\Models\Service;
use App\Models\SocialMedia;
use Faker\Generator as Faker;

$factory->define(SocialMedia::class, function (Faker $faker) {
    return [
        'type' => SocialMedia::TYPE_FACEBOOK,
        'url' => 'https://facebook.com/' . $faker->domainWord,
    ];
});

$factory->state(SocialMedia::class, 'twitter', function (Faker $faker) {
    return [
        'type' => SocialMedia::TYPE_TWITTER,
        'url' => 'https://twitter.com/' . $faker->domainWord,
    ];
});

$factory->state(SocialMedia::class, 'instagram', function (Faker $faker) {
    return [
        'type' => SocialMedia::TYPE_INSTAGRAM,
        'url' => 'https://www.instagram.com/' . $faker->domainWord,
    ];
});

$factory->state(SocialMedia::class, 'youtube', function (Faker $faker) {
    return [
        'type' => SocialMedia::TYPE_YOUTUBE,
        'url' => 'https://www.youtube.com/' . $faker->domainWord,
    ];
});

$factory->state(SocialMedia::class, 'service', []);
$factory->afterCreatingState(SocialMedia::class, 'service', function (SocialMedia $social, Faker $faker) {
    factory(Service::class)->create()->socialMedias()->save($social);
});

$factory->state(SocialMedia::class, 'organisation', []);
$factory->afterCreatingState(SocialMedia::class, 'organisation', function (SocialMedia $social, Faker $faker) {
    factory(Organisation::class)->create()->socialMedias()->save($social);
});
