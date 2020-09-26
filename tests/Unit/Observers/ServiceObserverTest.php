<?php

namespace Tests\Unit\Observers;

use App\Emails\StaleServiceDisabled\NotifyGlobalAdminEmail;
use App\Models\Service;
use App\Observers\ServiceObserver;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ServiceObserverTest extends TestCase
{
    public function test_service_disabled_email_sent_when_over_12_month_stale_service_updated_to_become_disabled()
    {
        Queue::fake();

        /** @var \App\Models\Service $service */
        $service = factory(Service::class)->create([
            'status' => Service::STATUS_ACTIVE,
            'last_modified_at' => Date::now()->subMonths(13),
        ]);

        $service->status = Service::STATUS_INACTIVE;

        $observer = new ServiceObserver();
        $observer->updated($service);

        Queue::assertPushedOn('notifications', NotifyGlobalAdminEmail::class);
        Queue::assertPushed(NotifyGlobalAdminEmail::class, function (NotifyGlobalAdminEmail $email): bool {
            $this->assertArrayHasKey('SERVICE_NAME', $email->values);
            return true;
        });
    }
}
