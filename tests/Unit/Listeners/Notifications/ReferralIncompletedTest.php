<?php

namespace Tests\Unit\Listeners\Notifications;

use App\Emails\ReferralIncompleted\NotifyClientEmail;
use App\Emails\ReferralIncompleted\NotifyRefereeEmail;
use App\Events\EndpointHit;
use App\Listeners\Notifications\ReferralIncompleted;
use App\Models\Referral;
use App\Models\User;
use App\Sms\ReferralIncompleted\NotifyClientSms;
use App\Sms\ReferralIncompleted\NotifyRefereeSms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ReferralIncompletedTest extends TestCase
{
    public function test_emails_sent_out(): void
    {
        Queue::fake();

        $referral = Referral::factory()->create([
            'email' => 'test@example.com',
            'referee_email' => 'test@example.com',
            'status' => Referral::STATUS_INCOMPLETED,
        ]);
        $referral->statusUpdates()->create([
            'user_id' => User::factory()->create()->id,
            'from' => Referral::STATUS_NEW,
            'to' => Referral::STATUS_INCOMPLETED,
        ]);

        $request = Request::create('')->setUserResolver(function () {
            return User::factory()->create();
        });
        $event = EndpointHit::onUpdate($request, '', $referral);
        $listener = new ReferralIncompleted();
        $listener->handle($event);

        Queue::assertPushedOn(config('queue.queues.notifications', 'default'), NotifyRefereeEmail::class);
        Queue::assertPushed(NotifyRefereeEmail::class, function (NotifyRefereeEmail $email) {
            $this->assertEquals(
                config('gov_uk_notify.notifications_template_ids.referral_incompleted.notify_referee.email'),
                $email->templateId
            );
            $this->assertEquals('emails.referral.incompleted.notify_referee.subject', $email->getSubject());
            $this->assertEquals('emails.referral.incompleted.notify_referee.content', $email->getContent());
            $this->assertArrayHasKey('REFEREE_NAME', $email->values);
            $this->assertArrayHasKey('SERVICE_NAME', $email->values);
            $this->assertArrayHasKey('REFERRAL_STATUS', $email->values);
            $this->assertArrayHasKey('REFERRAL_ID', $email->values);

            return true;
        });

        Queue::assertPushedOn(config('queue.queues.notifications', 'default'), NotifyClientEmail::class);
        Queue::assertPushed(NotifyClientEmail::class, function (NotifyClientEmail $email) {
            $this->assertEquals(
                config('gov_uk_notify.notifications_template_ids.referral_incompleted.notify_client.email'),
                $email->templateId
            );
            $this->assertEquals('emails.referral.incompleted.notify_client.subject', $email->getSubject());
            $this->assertEquals('emails.referral.incompleted.notify_client.content', $email->getContent());
            $this->assertArrayHasKey('REFERRAL_ID', $email->values);
            $this->assertArrayHasKey('SERVICE_NAME', $email->values);
            $this->assertArrayHasKey('REFERRAL_STATUS', $email->values);

            return true;
        });
    }

    public function test_sms_sent_out(): void
    {
        Queue::fake();

        $referral = Referral::factory()->create([
            'phone' => 'test@example.com',
            'referee_phone' => '07700000000',
            'status' => Referral::STATUS_INCOMPLETED,
        ]);
        $referral->statusUpdates()->create([
            'user_id' => User::factory()->create()->id,
            'from' => Referral::STATUS_NEW,
            'to' => Referral::STATUS_INCOMPLETED,
        ]);

        $request = Request::create('')->setUserResolver(function () {
            return User::factory()->create();
        });
        $event = EndpointHit::onUpdate($request, '', $referral);
        $listener = new ReferralIncompleted();
        $listener->handle($event);

        Queue::assertPushedOn(config('queue.queues.notifications', 'default'), NotifyRefereeSms::class);
        Queue::assertPushed(NotifyRefereeSms::class, function (NotifyRefereeSms $sms) {
            $this->assertArrayHasKey('REFEREE_NAME', $sms->values);
            $this->assertArrayHasKey('REFERRAL_ID', $sms->values);

            return true;
        });

        Queue::assertPushedOn(config('queue.queues.notifications', 'default'), NotifyClientSms::class);
        Queue::assertPushed(NotifyClientSms::class, function (NotifyClientSms $sms) {
            $this->assertArrayHasKey('CLIENT_INITIALS', $sms->values);
            $this->assertArrayHasKey('REFERRAL_ID', $sms->values);

            return true;
        });
    }

    public function test_both_email_and_sms_sent_out_to_client(): void
    {
        Queue::fake();

        $referral = Referral::factory()->create([
            'email' => 'test@example.com',
            'phone' => '07700000000',
            'referee_email' => 'test@example.com',
            'status' => Referral::STATUS_INCOMPLETED,
        ]);
        $referral->statusUpdates()->create([
            'user_id' => User::factory()->create()->id,
            'from' => Referral::STATUS_NEW,
            'to' => Referral::STATUS_INCOMPLETED,
        ]);

        $request = Request::create('')->setUserResolver(function () {
            return User::factory()->create();
        });
        $event = EndpointHit::onUpdate($request, '', $referral);
        $listener = new ReferralIncompleted();
        $listener->handle($event);

        Queue::assertPushedOn(config('queue.queues.notifications', 'default'), NotifyClientEmail::class);
        Queue::assertPushed(NotifyClientEmail::class, function (NotifyClientEmail $email) {
            $this->assertArrayHasKey('REFERRAL_ID', $email->values);
            $this->assertArrayHasKey('SERVICE_NAME', $email->values);
            $this->assertArrayHasKey('REFERRAL_STATUS', $email->values);

            return true;
        });

        Queue::assertPushedOn(config('queue.queues.notifications', 'default'), NotifyClientSms::class);
        Queue::assertPushed(NotifyClientSms::class, function (NotifyClientSms $sms) {
            $this->assertArrayHasKey('CLIENT_INITIALS', $sms->values);
            $this->assertArrayHasKey('REFERRAL_ID', $sms->values);

            return true;
        });
    }
}
