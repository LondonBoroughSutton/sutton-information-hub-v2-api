<?php

namespace Tests\Unit\Models;

use App\Models\LocalAuthority;
use Tests\TestCase;

class LocalAuthorityTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_have_a_name_property()
    {
        $localAuthority = factory(LocalAuthority::class)->create([]);
        $this->assertNotEmpty($localAuthority->name);
    }

    /**
     * @test
     */
    public function it_should_have_a_code_property()
    {
        $localAuthority = factory(LocalAuthority::class)->create([]);
        $this->assertNotEmpty($localAuthority->code);
    }

    /**
     * @test
     */
    public function it_should_have_a_region_method()
    {
        $englishAuthority = factory(localAuthority::class)->create([
            'code' => 'E06000010',
            'name' => 'Kingston upon Hull, City of',

        ]);
        $scottishAuthority = factory(localAuthority::class)->create([
            'code' => 'S12000013',
            'name' => 'Na h-Eileanan Siar',

        ]);
        $welshAuthority = factory(localAuthority::class)->create([
            'code' => 'W06000001',
            'name' => 'Isle of Anglesey',
            'alt_name' => 'Ynys Môn',

        ]);
        $northernIrishAuthority = factory(localAuthority::class)->create([
            'code' => 'N09000001',
            'name' => 'Antrim and Newtownabbey',

        ]);
        $this->assertTrue(method_exists($englishAuthority, 'region'));
        $this->assertEquals(LocalAuthority::REGION_ENGLAND, $englishAuthority->region());
        $this->assertEquals(LocalAuthority::REGION_SCOTLAND, $scottishAuthority->region());
        $this->assertEquals(LocalAuthority::REGION_WALES, $welshAuthority->region());
        $this->assertEquals(LocalAuthority::REGION_NORTHERN_IRELAND, $northernIrishAuthority->region());
    }

    /**
     * @test
     */
    public function it_should_have_a_users_method()
    {
        $localAuthority = factory(localAuthority::class)->create();
        $this->assertTrue(method_exists($localAuthority, 'users'));
    }
}
