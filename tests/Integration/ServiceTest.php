<?php

namespace Tests\Integration;

use App\Models\Service;
use Tests\TestCase;

class ServiceTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_persist_and_retrieve_records()
    {
        factory(Service::class, 10)->create();

        $services = Service::all();

        $this->assertCount(10, $services);
    }

    /**
     * @test
     */
    public function it_has_an_associated_organisation()
    {
        $service = factory(\App\Models\Service::class)->create();

        $this->assertInstanceOf(\App\Models\Organisation::class, $service->organisation);
    }

    /**
     * @test
     */
    public function it_can_have_an_associated_logo()
    {
        $service = factory(\App\Models\Service::class)->states('logo')->create();

        $this->assertInstanceOf(\App\Models\File::class, $service->logoFile);
    }

    /**
     * @test
     */
    public function it_can_have_associated_social_media()
    {
        $service = factory(\App\Models\Service::class)->states('social')->create();

        $this->assertInstanceOf(\App\Models\SocialMedia::class, $service->socialMedias->first());
    }
}
