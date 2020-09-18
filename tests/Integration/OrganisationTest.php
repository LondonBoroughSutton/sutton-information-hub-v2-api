<?php

namespace Tests\Integration;

use App\Models\File;
use App\Models\Location;
use App\Models\Organisation;
use App\Models\SocialMedia;
use Tests\TestCase;

class OrganisationTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_persist_and_retrieve_records()
    {
        factory(Organisation::class, 10)->create();

        $organisations = Organisation::all();

        $this->assertCount(10, $organisations);
    }

    /**
     * @test
     */
    public function it_can_have_an_associated_logo()
    {
        $organisation = factory(Organisation::class)->states('logo')->create();

        $this->assertInstanceOf(File::class, $organisation->logoFile);
    }

    /**
     * @test
     */
    public function it_can_have_associated_social_media()
    {
        $organisation = factory(Organisation::class)->states('social')->create();

        $this->assertInstanceOf(SocialMedia::class, $organisation->socialMedias->first());
    }

    /**
     * @test
     */
    public function it_can_have_an_associated_location()
    {
        $organisation = factory(Organisation::class)->states('location')->create();

        $this->assertInstanceOf(Location::class, $organisation->location);
    }
}
