<?php

namespace Tests\Unit\Models;

use App\Models\Location;
use Tests\TestCase;

class LocationTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_have_an_organisation_method()
    {
        $location = factory(Location::class)->create();
        $this->assertTrue(method_exists($location, 'organisation'));
    }
}
