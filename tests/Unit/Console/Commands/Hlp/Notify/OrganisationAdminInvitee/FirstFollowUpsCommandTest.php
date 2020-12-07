<?php

namespace Tests\Unit\Console\Commands\Hlp\Notify\OrganisationAdminInvitee;

use App\Console\Commands\Hlp\Notify\OrganisationAdminInvitee\FirstFollowUpsCommand;
use App\Emails\OrganisationAdminInviteFirstFollowUps\NotifyInviteeEmail;
use App\Models\Location;
use App\Models\Organisation;
use App\Models\OrganisationAdminInvite;
use App\Models\SocialMedia;
use App\Sms\OrganisationAdminInviteFirstFollowUps\NotifyInviteeSms;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class FirstFollowUpsCommandTest extends TestCase
{
    /**
     * @param int $week
     * @dataProvider weeksTwoToFiveDataProvider
     */
    public function test_emails_sent_for_weeks_two_to_five(int $week)
    {
        Queue::fake();

        $organisation = factory(Organisation::class)->create([
            'name' => 'Acme Org',
            'email' => 'acme.org@example.com',
            'description' => 'Lorem ipsum',
            'url' => 'http://acme.com',
            'phone' => '011300000000',
        ]);

        $organisation->location()->associate(
            factory(Location::class)->create([
                'address_line_1' => '1 Fake Street',
                'address_line_2' => 'Floor 2',
                'address_line_3' => 'Room 3',
                'city' => 'Leeds',
                'county' => 'West Yorkshire',
                'postcode' => 'LS1 2AB',
                'country' => 'United Kingdom',
            ])
        );

        $organisation->socialMedias()->create([
            'type' => SocialMedia::TYPE_FACEBOOK,
            'url' => 'http://facebook.com/AcmeOrg',
        ]);

        $organisation->save();

        factory(OrganisationAdminInvite::class)->create([
            'id' => 'test-id',
            'organisation_id' => $organisation->id,
            'email' => 'foo.org@example.com',
            'created_at' => Date::today()->subWeeks($week),
        ]);

        Artisan::call(FirstFollowUpsCommand::class);

        Queue::assertPushedOn('notifications', NotifyInviteeEmail::class);
        Queue::assertPushed(NotifyInviteeEmail::class, function (NotifyInviteeEmail $email) {
            $expectedValues = [
                'ORGANISATION_NAME' => 'Acme Org',
                'ORGANISATION_ADDRESS' => '1 Fake Street, Floor 2, Room 3, Leeds, West Yorkshire, LS1 2AB, United Kingdom',
                'ORGANISATION_URL' => 'http://acme.com',
                'ORGANISATION_EMAIL' => 'acme.org@example.com',
                'ORGANISATION_PHONE' => '011300000000',
                'ORGANISATION_SOCIAL_MEDIA' => 'Facebook: http://facebook.com/AcmeOrg',
                'ORGANISATION_DESCRIPTION' => 'Lorem ipsum',
                'INVITE_URL' => config('hlp.backend_uri') . '/organisation-admin-invites/test-id',
            ];

            return ($email->to === 'foo.org@example.com') && ($email->values == $expectedValues);
        });
    }

    /**
     * @param int $week
     * @dataProvider weeksTwoToFiveDataProvider
     */
    public function test_sms_sent_for_weeks_two_to_five(int $week)
    {
        Queue::fake();

        $organisation = factory(Organisation::class)->create([
            'name' => 'Acme Org',
            'email' => null,
            'description' => 'Lorem ipsum',
            'url' => 'http://acme.com',
            'phone' => '01236987450',
        ]);

        $organisation->location()->associate(
            factory(Location::class)->create([
                'address_line_1' => '1 Fake Street',
                'address_line_2' => 'Floor 2',
                'address_line_3' => 'Room 3',
                'city' => 'Leeds',
                'county' => 'West Yorkshire',
                'postcode' => 'LS1 2AB',
                'country' => 'United Kingdom',
            ])
        );

        $organisation->socialMedias()->create([
            'type' => SocialMedia::TYPE_FACEBOOK,
            'url' => 'http://facebook.com/AcmeOrg',
        ]);

        $organisation->save();

        factory(OrganisationAdminInvite::class)->create([
            'id' => 'test-id',
            'organisation_id' => $organisation->id,
            'sms' => '07896321450',
            'created_at' => Date::today()->subWeeks($week),
        ]);

        Artisan::call(FirstFollowUpsCommand::class);

        Queue::assertPushedOn('notifications', NotifyInviteeSms::class);
        Queue::assertPushed(NotifyInviteeSms::class, function (NotifyInviteeSms $sms) {
            $expectedValues = [
                'ORGANISATION_NAME' => 'Acme Org',
                'ORGANISATION_ADDRESS' => '1 Fake Street, Floor 2, Room 3, Leeds, West Yorkshire, LS1 2AB, United Kingdom',
                'ORGANISATION_URL' => 'http://acme.com',
                'ORGANISATION_EMAIL' => 'N/A',
                'ORGANISATION_PHONE' => '01236987450',
                'ORGANISATION_SOCIAL_MEDIA' => 'Facebook: http://facebook.com/AcmeOrg',
                'ORGANISATION_DESCRIPTION' => 'Lorem ipsum',
                'INVITE_URL' => config('hlp.backend_uri') . '/organisation-admin-invites/test-id',
            ];

            return ($sms->to === '07896321450') && ($sms->values == $expectedValues);
        });
    }

    /**
     * @param int $week
     * @dataProvider weeksBeforeAndAfterTwoToFiveDataProvider
     */
    public function test_emails_not_sent_for_weeks_before_and_after_two_to_five(int $week)
    {
        Queue::fake();

        factory(OrganisationAdminInvite::class)->create([
            'created_at' => Date::today()->subWeeks($week),
        ]);

        Artisan::call(FirstFollowUpsCommand::class);

        Queue::assertNotPushed(NotifyInviteeEmail::class);
    }

    /**
     * @param int $week
     * @dataProvider weeksBeforeAndAfterTwoToFiveDataProvider
     */
    public function test_sms_not_sent_for_weeks_before_and_after_two_to_five(int $week)
    {
        Queue::fake();

        factory(OrganisationAdminInvite::class)->create([
            'created_at' => Date::today()->subWeeks($week),
        ]);

        Artisan::call(FirstFollowUpsCommand::class);

        Queue::assertNotPushed(NotifyInviteeSms::class);
    }

    /**
     * @return \int[][]
     */
    public function weeksTwoToFiveDataProvider(): array
    {
        return [
            [1],
            [2],
            [3],
            [4],
        ];
    }

    /**
     * @return \int[][]
     */
    public function weeksBeforeAndAfterTwoToFiveDataProvider(): array
    {
        return [
            [-1],
            [0],
            [5],
            [6],
        ];
    }
}
