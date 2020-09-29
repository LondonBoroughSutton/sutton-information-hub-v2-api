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

    /**
     * @test
     */
    public function it_can_create_services_of_all_types()
    {
        $serviceTypes = [
            Service::TYPE_SERVICE,
            Service::TYPE_ACTIVITY,
            Service::TYPE_CLUB,
            Service::TYPE_GROUP,
            Service::TYPE_HELPLINE,
            Service::TYPE_INFORMATION,
            Service::TYPE_APP,
            Service::TYPE_ADVICE,
        ];

        foreach ($serviceTypes as $serviceType) {
            $service = factory(\App\Models\Service::class)->create([
                'type' => $serviceType,
            ]);
            $this->assertInstanceOf(Service::class, $service);
            $this->assertEquals($serviceType, $service->type);
        }
    }
}
