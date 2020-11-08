<?php

namespace Tests\Unit\Models;

use App\Models\Service;
use Faker\Factory as Faker;
use Tests\TestCase;

class ServiceTest extends TestCase
{
    public function setup(): void
    {
        parent::setUp();
        $this->faker = Faker::create('en_GB');
    }

    /**
     * @test
     */
    public function it_should_have_a_score_property()
    {
        $service = factory(Service::class)->create();

        $this->assertEquals(0, $service->score);
    }
}
