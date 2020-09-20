<?php

namespace Tests\Unit\Observers;

use App\Emails\OrganisationAdminInviteInitial\NotifyInviteeEmail;
use App\Generators\AdminUrlGenerator;
use App\Models\Location;
use App\Models\Organisation;
use App\Models\OrganisationAdminInvite;
use App\Models\SocialMedia;
use App\Observers\OrganisationAdminInviteObserver;
use App\Transformers\OrganisationInviteTransformer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrganisationAdminInviteObserverTest extends TestCase
{
    public function test_created_sends_emails_to_invitee()
    {
        Queue::fake();

        $organisationMock = $this->createMock(Organisation::class);
        $organisationMock->expects($this->any())
            ->method('__get')
            ->will($this->returnValueMap([
                ['name', 'Acme Org'],
                ['email', 'acme.org@example.com'],
                ['description', 'Lorem ipsum'],
            ]));

        $organisationAdminInviteMock = $this->createMock(OrganisationAdminInvite::class);
        $organisationAdminInviteMock->expects($this->any())
            ->method('__get')
            ->will($this->returnValueMap([
                ['email', 'foo.org@example.com'],
                ['organisation', $organisationMock],
            ]));

        $adminUrlGeneratorMock = $this->createMock(AdminUrlGenerator::class);
        $adminUrlGeneratorMock->expects($this->once())
            ->method('generateOrganisationAdminInviteUrl')
            ->with($organisationAdminInviteMock)
            ->willReturn('test-invite-url');

        $organisationInviteTransformerMock = $this->createMock(OrganisationInviteTransformer::class);
        $organisationInviteTransformerMock->expects($this->once())
            ->method('transformAddress')
            ->with(null)
            ->willReturn(null);
        $organisationInviteTransformerMock->expects($this->once())
            ->method('transformSocialMedias')
            ->with(null)
            ->willReturn(null);

        $observer = new OrganisationAdminInviteObserver(
            $adminUrlGeneratorMock,
            $organisationInviteTransformerMock
        );
        $observer->created($organisationAdminInviteMock);

        Queue::assertPushedOn('notifications', NotifyInviteeEmail ::class);
        Queue::assertPushed(NotifyInviteeEmail ::class, function (NotifyInviteeEmail $email): bool {
            $expectedValues = [
                'ORGANISATION_NAME' => 'Acme Org',
                'ORGANISATION_ADDRESS' => 'N/A',
                'ORGANISATION_URL' => 'N/A',
                'ORGANISATION_EMAIL' => 'acme.org@example.com',
                'ORGANISATION_PHONE' => 'N/A',
                'ORGANISATION_SOCIAL_MEDIA' => 'N/A',
                'ORGANISATION_DESCRIPTION' => 'Lorem ipsum',
                'INVITE_URL' => 'test-invite-url',
            ];

            return ($email->to === 'foo.org@example.com') && ($email->values == $expectedValues);
        });
    }

    public function test_created_sends_emails_to_invitee_with_all_fields()
    {
        Queue::fake();

        $locationMock = $this->createMock(Location::class);

        $socialMediaMock = $this->createMock(SocialMedia::class);
        $socialMedias = new Collection([$socialMediaMock]);

        $organisationMock = $this->createMock(Organisation::class);
        $organisationMock->expects($this->any())
            ->method('__get')
            ->will($this->returnValueMap([
                ['name', 'Acme Org'],
                ['email', 'acme.org@example.com'],
                ['description', 'Lorem ipsum'],
                ['url', 'http://acme.com'],
                ['phone', '011300000000'],
                ['location', $locationMock],
                ['socialMedias', $socialMedias],
            ]));

        $organisationAdminInviteMock = $this->createMock(OrganisationAdminInvite::class);
        $organisationAdminInviteMock->expects($this->any())
            ->method('__get')
            ->will($this->returnValueMap([
                ['email', 'foo.org@example.com'],
                ['organisation', $organisationMock],
            ]));

        $adminUrlGeneratorMock = $this->createMock(AdminUrlGenerator::class);
        $adminUrlGeneratorMock->expects($this->once())
            ->method('generateOrganisationAdminInviteUrl')
            ->with($organisationAdminInviteMock)
            ->willReturn('test-invite-url');

        $organisationInviteTransformerMock = $this->createMock(OrganisationInviteTransformer::class);
        $organisationInviteTransformerMock->expects($this->once())
            ->method('transformAddress')
            ->with($locationMock)
            ->willReturn('1 Fake Street, Floor 2, Room 3, Leeds, West Yorkshire, LS1 2AB, United Kingdom');
        $organisationInviteTransformerMock->expects($this->once())
            ->method('transformSocialMedias')
            ->with($socialMedias)
            ->willReturn('Facebook: http://facebook.com/AcmeOrg');

        $observer = new OrganisationAdminInviteObserver(
            $adminUrlGeneratorMock,
            $organisationInviteTransformerMock
        );
        $observer->created($organisationAdminInviteMock);

        Queue::assertPushedOn('notifications', NotifyInviteeEmail ::class);
        Queue::assertPushed(NotifyInviteeEmail ::class, function (NotifyInviteeEmail $email): bool {
            $expectedValues = [
                'ORGANISATION_NAME' => 'Acme Org',
                'ORGANISATION_ADDRESS' => '1 Fake Street, Floor 2, Room 3, Leeds, West Yorkshire, LS1 2AB, United Kingdom',
                'ORGANISATION_URL' => 'http://acme.com',
                'ORGANISATION_EMAIL' => 'acme.org@example.com',
                'ORGANISATION_PHONE' => '011300000000',
                'ORGANISATION_SOCIAL_MEDIA' => 'Facebook: http://facebook.com/AcmeOrg',
                'ORGANISATION_DESCRIPTION' => 'Lorem ipsum',
                'INVITE_URL' => 'test-invite-url',
            ];

            return ($email->to === 'foo.org@example.com') && ($email->values == $expectedValues);
        });
    }

    public function test_created_does_not_send_emails_to_invitee_when_email_is_null()
    {
        Queue::fake();

        $organisationAdminInviteMock = $this->createMock(OrganisationAdminInvite::class);
        $organisationAdminInviteMock->expects($this->any())
            ->method('__get')
            ->will($this->returnValueMap([
                ['email', null],
            ]));

        $adminUrlGeneratorMock = $this->createMock(AdminUrlGenerator::class);
        $adminUrlGeneratorMock->expects($this->never())
            ->method('generateOrganisationAdminInviteUrl');

        $organisationInviteTransformerMock = $this->createMock(OrganisationInviteTransformer::class);

        $observer = new OrganisationAdminInviteObserver(
            $adminUrlGeneratorMock,
            $organisationInviteTransformerMock
        );
        $observer->created($organisationAdminInviteMock);

        Queue::assertNotPushed(NotifyInviteeEmail ::class);
    }
}
