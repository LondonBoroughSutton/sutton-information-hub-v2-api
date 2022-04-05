<?php

namespace Tests\Integration\Importers;

use App\Models\Service;
use CqcLocationIdSeeder;
use Tests\TestCase;

class CqcLocationIdSeederTest extends TestCase
{
    private $cqcLocationIds = [
        '07019201-9223-42d6-813c-9636bf6cf421' => '1-9654205805',
        '6648af95-8a21-4514-875e-2274acd63749' => '1-400283026',
        '89278646-2f38-48c5-90d2-b48ab3bfaf9f' => '1-4211948948',
        '5f6d82c8-6c08-4b80-bee5-fe4d560aaf1f' => '1-12100652788',
        'dbd53edc-33f3-4121-8211-a0cb01a95dee' => '1-2981698841',
    ];

    public function setUp(): void
    {
        parent::setUp();

        foreach ($this->cqcLocationIds as $uuid => $cqcLocationId) {
            factory(Service::class)->create([
                'id' => $uuid,
                'cqc_location_id' => null,
            ]);
        }
    }

    /**
     * @test
     */
    public function seederUpdatesServices()
    {
        foreach ($this->cqcLocationIds as $uuid => $cqcLocationId) {
            $this->assertDatabaseHas('services', [
                'id' => $uuid,
                'cqc_location_id' => null,
            ]);
        }
        $this->seed(CqcLocationIdSeeder::class);

        foreach ($this->cqcLocationIds as $uuid => $cqcLocationId) {
            $this->assertDatabaseHas(
                'services',
                [
                    'id' => $uuid,
                    'cqc_location_id' => $cqcLocationId,
                ],
            );
        }
    }
}
