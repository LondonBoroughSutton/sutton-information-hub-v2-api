<?php

namespace Tests\Integration;

use App\Models\LocalAuthority;
use Tests\TestCase;

class LocalAuthorityTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_persist_and_retrieve_records()
    {
        factory(LocalAuthority::class, 10)->create();

        $localAuthorities = LocalAuthority::all();

        $this->assertCount(10, $localAuthorities);
    }

    /**
     * @test
     */
    public function it_has_associated_users()
    {
        $localAuthority = factory(\App\Models\LocalAuthority::class)->create();

        factory(\App\Models\User::class, 2)->create([
            'local_authority_id' => $localAuthority->id,
        ]);

        $this->assertInstanceOf(\App\Models\User::class, $localAuthority->users->first());
    }
}
