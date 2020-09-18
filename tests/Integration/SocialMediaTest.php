<?php

namespace Tests\Integration;

use App\Models\Organisation;
use App\Models\Service;
use App\Models\SocialMedia;
use Tests\TestCase;

class SocialMediaTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_persist_and_retrieve_records()
    {
        factory(SocialMedia::class, 10)->create();

        $socialMedias = SocialMedia::all();

        $this->assertCount(10, $socialMedias);
    }

    /**
     * @test
     */
    public function it_can_have_an_associated_service()
    {
        $socialMedia = factory(SocialMedia::class)->states('service')->create();

        $this->assertInstanceOf(Service::class, $socialMedia->sociable);
    }

    /**
     * @test
     */
    public function it_can_have_an_associated_organisation()
    {
        $socialMedia = factory(SocialMedia::class)->states('organisation')->create();

        $this->assertInstanceOf(Organisation::class, $socialMedia->sociable);
    }
}
