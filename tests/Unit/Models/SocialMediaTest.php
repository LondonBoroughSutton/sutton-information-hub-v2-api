<?php

namespace Tests\Unit\Models;

use App\Models\SocialMedia;
use Tests\TestCase;

class SocialMediaTest extends TestCase
{

    /**
     * @test
     */
    public function it_should_have_a_type_property()
    {
        $socialMedia = factory(SocialMedia::class)->create();
        $this->assertEquals(SocialMedia::TYPE_FACEBOOK, $socialMedia->type);
    }

    /**
     * @test
     */
    public function it_should_have_a_url_property()
    {
        $socialMedia = factory(SocialMedia::class)->create();
        $this->assertNotEmpty($socialMedia->url);
    }

    /**
     * @test
     */
    public function it_should_have_a_sociable_method()
    {
        $socialMedia = factory(SocialMedia::class)->create();
        $this->assertTrue(method_exists($socialMedia, 'sociable'));
    }
}
