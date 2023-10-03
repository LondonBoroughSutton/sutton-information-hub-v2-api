<?php

namespace Tests\Feature;

use App\Events\EndpointHit;
use App\Models\Audit;
use App\Models\Notification;
use App\Models\Organisation;
use App\Models\Referral;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Laravel\Passport\Passport;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    /*
     * List all the notifications.
     */

    /**
     * @test
     */
    public function guest_cannot_list_them()
    {
        $response = $this->json('GET', '/core/v1/notifications');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @test
     */
    public function service_worker_cannot_list_them()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceWorker($service);

        Passport::actingAs($user);

        $response = $this->json('GET', '/core/v1/notifications');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function service_admin_cannot_list_them()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceAdmin($service);

        Passport::actingAs($user);

        $response = $this->json('GET', '/core/v1/notifications');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function organisation_admin_cannot_list_them()
    {
        /**
         * @var \App\Models\Organisation $organisation
         * @var \App\Models\User $user
         */
        $organisation = Organisation::factory()->create();
        $user = User::factory()->create();
        $user->makeOrganisationAdmin($organisation);

        Passport::actingAs($user);

        $response = $this->json('GET', '/core/v1/notifications');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function global_admin_cannot_list_them()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeGlobalAdmin();

        Passport::actingAs($user);

        $response = $this->json('GET', '/core/v1/notifications');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function super_admin_can_list_them()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $notification = $user->notifications()->create([
            'channel' => Notification::CHANNEL_EMAIL,
            'recipient' => 'test@example.com',
            'message' => 'This is a test',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        Passport::actingAs($user);

        $response = $this->json('GET', '/core/v1/notifications');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $notification->id,
            'notifiable_type' => 'users',
            'notifiable_id' => $user->id,
            'channel' => $notification->channel,
            'recipient' => $notification->recipient,
            'message' => $notification->message,
            'created_at' => $notification->created_at->format(CarbonImmutable::ISO8601),
            'updated_at' => $notification->updated_at->format(CarbonImmutable::ISO8601),
        ]);
    }

    /**
     * @test
     */
    public function super_admin_can_list_them_for_specific_user()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $notification = $user->notifications()->create([
            'channel' => Notification::CHANNEL_EMAIL,
            'recipient' => 'test@example.com',
            'message' => 'This is a test',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);
        $anotherNotification = Notification::create([
            'channel' => Notification::CHANNEL_SMS,
            'recipient' => '07700000000',
            'message' => 'Another notification',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/notifications?filter[user_id]={$user->id}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $notification->id]);
        $response->assertJsonMissing(['id' => $anotherNotification->id]);
    }

    /**
     * @test
     */
    public function super_admin_can_list_them_for_referral()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $referral = Referral::factory()->create();
        $notification = $referral->notifications()->create([
            'channel' => Notification::CHANNEL_EMAIL,
            'recipient' => 'test@example.com',
            'message' => 'This is a test',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);
        $anotherNotification = Notification::create([
            'channel' => Notification::CHANNEL_SMS,
            'recipient' => '07700000000',
            'message' => 'Another notification',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/notifications?filter[referral_id]={$referral->id}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $notification->id]);
        $response->assertJsonMissing(['id' => $anotherNotification->id]);
    }

    /**
     * @test
     */
    public function super_admin_can_list_them_for_service()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $service = Service::factory()->create();
        $notification = $service->notifications()->create([
            'channel' => Notification::CHANNEL_EMAIL,
            'recipient' => 'test@example.com',
            'message' => 'This is a test',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);
        $anotherNotification = Notification::create([
            'channel' => Notification::CHANNEL_SMS,
            'recipient' => '07700000000',
            'message' => 'Another notification',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/notifications?filter[service_id]={$service->id}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $notification->id]);
        $response->assertJsonMissing(['id' => $anotherNotification->id]);
    }

    /**
     * @test
     */
    public function audit_created_when_listed()
    {
        $this->fakeEvents();

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $notification = $user->notifications()->create([
            'channel' => Notification::CHANNEL_EMAIL,
            'recipient' => 'test@example.com',
            'message' => 'This is a test',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        Passport::actingAs($user);

        $this->json('GET', '/core/v1/notifications');

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getUser()->id === $user->id);
        });
    }

    /*
     * Get a specific notification.
     */

    /**
     * @test
     */
    public function guest_cannot_view_one()
    {
        $notification = Notification::create([
            'channel' => Notification::CHANNEL_EMAIL,
            'recipient' => 'test@example.com',
            'message' => 'This is a test',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $response = $this->json('GET', "/core/v1/notifications/{$notification->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @test
     */
    public function service_worker_cannot_view_one()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceWorker($service);
        $notification = Notification::create([
            'channel' => Notification::CHANNEL_EMAIL,
            'recipient' => 'test@example.com',
            'message' => 'This is a test',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/notifications/{$notification->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function service_admin_cannot_view_one()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceAdmin($service);
        $notification = Notification::create([
            'channel' => Notification::CHANNEL_EMAIL,
            'recipient' => 'test@example.com',
            'message' => 'This is a test',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/notifications/{$notification->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function organisation_admin_cannot_view_one()
    {
        /**
         * @var \App\Models\Organisation $organisation
         * @var \App\Models\User $user
         */
        $organisation = Organisation::factory()->create();
        $user = User::factory()->create();
        $user->makeOrganisationAdmin($organisation);
        $notification = Notification::create([
            'channel' => Notification::CHANNEL_EMAIL,
            'recipient' => 'test@example.com',
            'message' => 'This is a test',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/notifications/{$notification->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function global_admin_cannot_view_one()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeGlobalAdmin();
        $notification = Notification::create([
            'channel' => Notification::CHANNEL_EMAIL,
            'recipient' => 'test@example.com',
            'message' => 'This is a test',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/notifications/{$notification->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function super_admin_can_view_one()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $notification = Notification::create([
            'channel' => Notification::CHANNEL_EMAIL,
            'recipient' => 'test@example.com',
            'message' => 'This is a test',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/notifications/{$notification->id}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $notification->id,
            'notifiable_type' => null,
            'notifiable_id' => null,
            'channel' => Notification::CHANNEL_EMAIL,
            'recipient' => 'test@example.com',
            'message' => 'This is a test',
            'created_at' => $this->now->format(CarbonImmutable::ISO8601),
            'updated_at' => $this->now->format(CarbonImmutable::ISO8601),
        ]);
    }

    /**
     * @test
     */
    public function audit_created_when_viewed()
    {
        $this->fakeEvents();

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $notification = Notification::create([
            'channel' => Notification::CHANNEL_EMAIL,
            'recipient' => 'test@example.com',
            'message' => 'This is a test',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        Passport::actingAs($user);

        $this->json('GET', "/core/v1/notifications/{$notification->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $notification) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $notification->id);
        });
    }
}
