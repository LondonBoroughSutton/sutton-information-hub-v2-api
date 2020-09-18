<?php

namespace Tests\Unit\Models;

use App\Models\Organisation;
use Faker\Factory as Faker;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrganisationTest extends TestCase
{
    public function setup(): void
    {
        parent::setUp();
        $this->faker = Faker::create('en_GB');
    }
    /**
     * @test
     */
    public function it_should_have_a_slug_property()
    {
        $name = $this->faker->unique()->company;
        $this->expectException(\Illuminate\Database\QueryException::class);

        Organisation::create([
            'name' => $name,
            'description' => 'This organisation provides x service.',
        ]);
        $organisation = factory(Organisation::class)->create([]);
        $this->assertNotEmpty($organisation->slug);
    }

    /**
     * @test
     */
    public function it_should_have_a_name_property()
    {
        $name = $this->faker->unique()->company;
        $this->expectException(\Illuminate\Database\QueryException::class);
        Organisation::create([
            'slug' => Str::slug($name) . '-' . mt_rand(1, 1000),
            'description' => 'This organisation provides x service.',
        ]);
        $organisation = factory(Organisation::class)->create([]);
        $this->assertNotEmpty($organisation->name);
    }

    /**
     * @test
     */
    public function it_should_have_a_description_property()
    {
        $name = $this->faker->unique()->company;
        $this->expectException(\Illuminate\Database\QueryException::class);
        Organisation::create([
            'slug' => Str::slug($name) . '-' . mt_rand(1, 1000),
            'name' => $name,
        ]);
        $organisation = factory(Organisation::class)->create();
        $this->assertNotEmpty($organisation->description);
    }

    /**
     * @test
     */
    public function it_should_have_an_optional_url_property()
    {
        $organisation = factory(Organisation::class)->create([
            'url' => $this->faker->url,
        ]);
        $this->assertNotEmpty($organisation->url);
    }

    /**
     * @test
     */
    public function it_should_have_an_optional_email_property()
    {
        $organisation = factory(Organisation::class)->create([
            'email' => $this->faker->safeEmail,
        ]);
        $this->assertNotEmpty($organisation->email);
    }

    /**
     * @test
     */
    public function it_should_have_an_optional_phone_property()
    {
        $organisation = factory(Organisation::class)->create([
            'phone' => $this->faker->phoneNumber,
        ]);
        $this->assertNotEmpty($organisation->phone);
    }

    /**
     * @test
     */
    public function it_should_have_a_social_medias_method()
    {
        $organisation = factory(Organisation::class)->create();
        $this->assertTrue(method_exists($organisation, 'socialMedias'));
    }

    /**
     * @test
     */
    public function it_should_have_a_location_method()
    {
        $organisation = factory(Organisation::class)->create();
        $this->assertTrue(method_exists($organisation, 'location'));
    }
}
