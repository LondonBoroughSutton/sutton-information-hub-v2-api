<?php

namespace App\Observers;

use App\Emails\OrganisationAdminInviteInitial\NotifyInviteeEmail;
use App\Generators\AdminUrlGenerator;
use App\Models\Notification;
use App\Models\OrganisationAdminInvite;
use App\Sms\OrganisationAdminInviteInitial\NotifyInviteeSms;
use App\Transformers\OrganisationInviteTransformer;

class OrganisationAdminInviteObserver
{
    /**
     * @var \App\Generators\AdminUrlGenerator
     */
    protected $adminUrlGenerator;

    /**
     * @var \App\Transformers\OrganisationInviteTransformer
     */
    protected $transformer;

    /**
     * OrganisationAdminInviteObserver constructor.
     *
     * @param \App\Generators\AdminUrlGenerator $adminUrlGenerator
     * @param \App\Transformers\OrganisationInviteTransformer $transformer
     */
    public function __construct(AdminUrlGenerator $adminUrlGenerator, OrganisationInviteTransformer $transformer)
    {
        $this->adminUrlGenerator = $adminUrlGenerator;
        $this->transformer = $transformer;
    }

    /**
     * Handle the organisation admin invite "created" event.
     *
     * @param \App\Models\OrganisationAdminInvite $organisationAdminInvite
     */
    public function created(OrganisationAdminInvite $organisationAdminInvite)
    {
        // Send notification to the invitee.
        if ($organisationAdminInvite->email !== null) {
            Notification::sendEmail(new NotifyInviteeEmail(
                $organisationAdminInvite->email,
                [
                    'ORGANISATION_NAME' => $organisationAdminInvite->organisation->name,
                    'ORGANISATION_ADDRESS' => $this->transformer->transformAddress(
                        $organisationAdminInvite->organisation->location
                    ) ?: 'N/A',
                    'ORGANISATION_URL' => $organisationAdminInvite->organisation->url ?: 'N/A',
                    'ORGANISATION_EMAIL' => $organisationAdminInvite->organisation->email ?: 'N/A',
                    'ORGANISATION_PHONE' => $organisationAdminInvite->organisation->phone ?: 'N/A',
                    'ORGANISATION_SOCIAL_MEDIA' => $this->transformer->transformSocialMedias(
                        $organisationAdminInvite->organisation->socialMedias
                    ) ?: 'N/A',
                    'ORGANISATION_DESCRIPTION' => $organisationAdminInvite->organisation->description,
                    'INVITE_URL' => $this->adminUrlGenerator->generateOrganisationAdminInviteUrl(
                        $organisationAdminInvite
                    ),
                ]
            ));
        } elseif ($organisationAdminInvite->sms !== null) {
            Notification::sendSms(new NotifyInviteeSms(
                $organisationAdminInvite->sms,
                [
                    'ORGANISATION_NAME' => $organisationAdminInvite->organisation->name,
                    'ORGANISATION_ADDRESS' => $this->transformer->transformAddress(
                        $organisationAdminInvite->organisation->location
                    ) ?: 'N/A',
                    'ORGANISATION_URL' => $organisationAdminInvite->organisation->url ?: 'N/A',
                    'ORGANISATION_EMAIL' => $organisationAdminInvite->organisation->email ?: 'N/A',
                    'ORGANISATION_PHONE' => $organisationAdminInvite->organisation->phone ?: 'N/A',
                    'ORGANISATION_SOCIAL_MEDIA' => $this->transformer->transformSocialMedias(
                        $organisationAdminInvite->organisation->socialMedias
                    ) ?: 'N/A',
                    'ORGANISATION_DESCRIPTION' => $organisationAdminInvite->organisation->description,
                    'INVITE_URL' => $this->adminUrlGenerator->generateOrganisationAdminInviteUrl(
                        $organisationAdminInvite
                    ),
                ]
            ));
        }
    }
}
