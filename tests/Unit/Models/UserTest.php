<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_have_a_localAuthority_method()
    {
        $user = factory(User::class)->create();
        $this->assertTrue(method_exists($user, 'localAuthority'));
    }

    /**
     * @test
     */
    public function it_should_have_a_location_method()
    {
        $user = factory(User::class)->create();
        $this->assertTrue(method_exists($user, 'location'));
    }
}
