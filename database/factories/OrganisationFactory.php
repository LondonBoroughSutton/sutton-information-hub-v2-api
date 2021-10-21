<?php

use App\Models\Organisation;
use App\Models\SocialMedia;
use Faker\Generator as Faker;
use Illuminate\Support\Str;

$factory->define(Organisation::class, function (Faker $faker) {
    $name = $faker->unique()->words(3, true) . ' ' . mt_rand(1, 1000);

    return [
        'slug' => Str::slug($name),
        'name' => $name . $faker->companySuffix,
        'description' => 'This organisation provides x service.',
        'url' => $faker->url,
        'email' => $faker->safeEmail,
        'phone' => random_uk_phone(),
    ];
});

$factory->state(Organisation::class, 'social-media', []);

$factory->afterCreatingState(Organisation::class, 'social-media', function ($organisation, $faker) {
    $organisation->socialMedias()->create([
        'type' => SocialMedia::TYPE_TWITTER,
        'url' => "https://twitter.com/{$faker->domainWord()}/",
    ]);
});
