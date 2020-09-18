<?php

namespace Tests\Integration;

use App\Models\Location;
use Tests\TestCase;

class LocationTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_persist_and_retrieve_records()
    {
        factory(Location::class, 10)->create();

        $locations = Location::all();

        $this->assertCount(10, $locations);
    }

    /**
     * @test
     */
    public function it_has_an_associated_organisation()
    {
        $location = factory(\App\Models\Location::class)->states('organisation')->create();

        $this->assertInstanceOf(\App\Models\Organisation::class, $location->organisation);
    }
}
