<?php

namespace Tests\Unit\Console\Commands\Ck;

use App\Console\Commands\Ck\EndActiveServicesCommand;
use App\Models\Service;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class EndActiveServicesCommandTest extends TestCase
{
    public function test_active_services_past_there_end_date_are_made_inactive(): void
    {
        /** @var \App\Models\Service $service */
        $service = Service::factory()->create([
            'status' => Service::STATUS_ACTIVE,
            'ends_at' => Date::now()->subDay(),
        ]);

        Artisan::call(EndActiveServicesCommand::class);

        $service->refresh();

        $this->assertTrue($service->status === Service::STATUS_INACTIVE);
    }

    public function test_active_services_with_future_end_date_are_not_made_inactive(): void
    {
        /** @var \App\Models\Service $service */
        $service = Service::factory()->create([
            'status' => Service::STATUS_ACTIVE,
            'ends_at' => Date::now()->addDay(),
        ]);

        Artisan::call(EndActiveServicesCommand::class);

        $service->refresh();

        $this->assertTrue($service->status === Service::STATUS_ACTIVE);
    }

    public function test_active_services_with_null_end_date_are_not_made_inactive(): void
    {
        /** @var \App\Models\Service $service */
        $service = Service::factory()->create([
            'status' => Service::STATUS_ACTIVE,
            'ends_at' => null,
        ]);

        Artisan::call(EndActiveServicesCommand::class);

        $service->refresh();

        $this->assertTrue($service->status === Service::STATUS_ACTIVE);
    }
}
