<?php

namespace Tests\Integration\Models;

use App\Models\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_persist_and_retrieve_records()
    {
        factory(User::class, 10)->create();

        $users = User::all();

        $this->assertCount(10, $users);
    }

    /**
     * @test
     */
    public function it_can_have_an_associated_organisation()
    {
        $user = factory(\App\Models\User::class)->create([
            'local_authority_id' => factory(\App\Models\LocalAuthority::class)->create()->id,
        ]);

        $this->assertInstanceOf(\App\Models\LocalAuthority::class, $user->local_authority);
    }

    /**
     * @test
     */
    public function it_can_have_an_associated_location()
    {
        $user = factory(\App\Models\User::class)->create([
            'location_id' => factory(\App\Models\Location::class)->create()->id,
        ]);

        $this->assertInstanceOf(\App\Models\Location::class, $user->location);
    }
}
