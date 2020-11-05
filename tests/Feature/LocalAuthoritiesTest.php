<?php

namespace Tests\Feature;

use App\Models\LocalAuthority;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Tests\TestCase;

class LocalAuthoritiesTest extends TestCase
{
    /**
     * @test
     */
    public function guest_can_list_local_authorities()
    {
        $localAuthorities = factory(LocalAuthority::class, 5)->create();

        $response = $this->json('GET', '/core/v1/local-authorities');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(5, 'data');
        $response->assertJsonFragment([
            'id' => $localAuthorities->get(0)->id,
            'name' => $localAuthorities->get(0)->name,
            'alt_name' => $localAuthorities->get(0)->alt_name,
            'code' => $localAuthorities->get(0)->code,
            'region' => $localAuthorities->get(0)->region(),
        ]);
    }

    /**
     * @test
     */
    public function guest_can_filter_local_authorities_by_region()
    {
        $welshLocalAuthorities = factory(LocalAuthority::class, 2)->states('welsh')->create();
        factory(LocalAuthority::class, 2)->create();
        factory(LocalAuthority::class, 2)->states('scottish')->create();
        factory(LocalAuthority::class, 2)->states('n_irish')->create();

        $response = $this->json('GET', '/core/v1/local-authorities?filter[region]=' . Str::slug(LocalAuthority::REGION_WALES));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment([
            'id' => $welshLocalAuthorities->get(0)->id,
            'name' => $welshLocalAuthorities->get(0)->name,
            'alt_name' => $welshLocalAuthorities->get(0)->alt_name,
            'code' => $welshLocalAuthorities->get(0)->code,
            'region' => $welshLocalAuthorities->get(0)->region(),
        ]);
        $response->assertJsonFragment([
            'id' => $welshLocalAuthorities->get(1)->id,
            'name' => $welshLocalAuthorities->get(1)->name,
            'alt_name' => $welshLocalAuthorities->get(1)->alt_name,
            'code' => $welshLocalAuthorities->get(1)->code,
            'region' => $welshLocalAuthorities->get(1)->region(),
        ]);
    }
}
