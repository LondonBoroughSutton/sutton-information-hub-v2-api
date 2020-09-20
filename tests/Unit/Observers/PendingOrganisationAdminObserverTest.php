<?php

namespace Tests\Unit\Observers;

use App\Emails\PendingOrganisationAdminConfirmation\NotifyPendingOrganisationAdminEmail;
use App\Generators\AdminUrlGenerator;
use App\Models\Organisation;
use App\Models\PendingOrganisationAdmin;
use App\Observers\PendingOrganisationAdminObserver;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PendingOrganisationAdminObserverTest extends TestCase
{
    public function test_created_sends_emails_to_pending_organisation_admin()
    {
        Queue::fake();

        $organisationMock = $this->createMock(Organisation::class);
        $organisationMock->expects($this->any())
            ->method('__get')
            ->will($this->returnValueMap([
                ['name', 'Acme Org'],
            ]));

        $pendingOrganisationAdminMock = $this->createMock(PendingOrganisationAdmin::class);
        $pendingOrganisationAdminMock->expects($this->any())
            ->method('__get')
            ->will($this->returnValueMap([
                ['email', 'foo.org@example.com'],
                ['organisation', $organisationMock],
            ]));

        $adminUrlGeneratorMock = $this->createMock(AdminUrlGenerator::class);
        $adminUrlGeneratorMock->expects($this->once())
            ->method('generatePendingOrganisationAdminConfirmationUrl')
            ->with($pendingOrganisationAdminMock)
            ->willReturn('test-invite-url');

        $observer = new PendingOrganisationAdminObserver($adminUrlGeneratorMock);
        $observer->created($pendingOrganisationAdminMock);

        Queue::assertPushedOn('notifications', NotifyPendingOrganisationAdminEmail ::class);
        Queue::assertPushed(
            NotifyPendingOrganisationAdminEmail ::class,
            function (NotifyPendingOrganisationAdminEmail $email): bool {
                $expectedValues = [
                    'ORGANISATION_NAME' => 'Acme Org',
                    'CONFIRM_EMAIL_URL' => 'test-invite-url',
                ];

                return ($email->to === 'foo.org@example.com') && ($email->values == $expectedValues);
            }
        );
    }
}
