<?php

namespace App\Console\Commands\Hlp\Notify\OrganisationAdminInvitee;

use App\Emails\OrganisationAdminInviteFirstFollowUps\NotifyInviteeEmail;
use App\Generators\AdminUrlGenerator;
use App\Models\Notification;
use App\Models\OrganisationAdminInvite;
use App\Transformers\OrganisationInviteTransformer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class FirstFollowUpsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hlp:notify:organisation-admin-invitee:first-follow-ups';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends notifications out to the organisation admin invitees with the first follow ups';

    /**
     * Execute the console command.
     *
     * @param \App\Generators\AdminUrlGenerator $adminUrlGenerator
     * @param \App\Transformers\OrganisationInviteTransformer $transformer
     */
    public function handle(AdminUrlGenerator $adminUrlGenerator, OrganisationInviteTransformer $transformer): void
    {
        $dates = array_map(function (int $week): string {
            return Date::today()->subWeeks($week)->toDateString();
        }, range(1, 4));

        $organisationAdminInvites = OrganisationAdminInvite::query()
            ->with('organisation', 'organisation.location', 'organisation.socialMedias')
            ->whereNotNull('email')
            ->whereIn(DB::raw('cast(`created_at` as date)'), $dates)
            ->get();

        foreach ($organisationAdminInvites as $organisationAdminInvite) {
            Notification::sendEmail(
                new NotifyInviteeEmail(
                    $organisationAdminInvite->email,
                    [
                        'ORGANISATION_NAME' => $organisationAdminInvite->organisation->name,
                        'ORGANISATION_ADDRESS' => $transformer->transformAddress(
                            $organisationAdminInvite->organisation->location
                        ) ?: 'N/A',
                        'ORGANISATION_URL' => $organisationAdminInvite->organisation->url ?: 'N/A',
                        'ORGANISATION_EMAIL' => $organisationAdminInvite->organisation->email ?: 'N/A',
                        'ORGANISATION_PHONE' => $organisationAdminInvite->organisation->phone ?: 'N/A',
                        'ORGANISATION_SOCIAL_MEDIA' => $transformer->transformSocialMedias(
                            $organisationAdminInvite->organisation->socialMedias
                        ) ?: 'N/A',
                        'ORGANISATION_DESCRIPTION' => $organisationAdminInvite->organisation->description,
                        'INVITE_URL' => $adminUrlGenerator->generateOrganisationAdminInviteUrl(
                            $organisationAdminInvite
                        ),
                    ]
                )
            );
        }
    }
}
