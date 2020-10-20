<?php

namespace App\Emails\OrganisationAdminInviteSecondFollowUps;

use App\Emails\Email;

class NotifyInviteeEmail extends Email
{
    /**
     * @return string
     */
    protected function getTemplateId(): string
    {
        return config('hlp.notifications_template_ids.organisation_admin_invite_second_follow_ups.notify_invitee.email');
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return <<<'EOT'
Dear ((ORGANISATION_NAME)),
We wrote to you five weeks ago to invite you to claim a listing for name of organisation on NHS Connect.
Your organisation was recommended to us as an important source of support and we would still very much like to add you to our new online directory. Your listing will then be accessible to citizens, to professionals supporting citizens (such as GPs or Link Workers), as well as to funders and commissioners.
Please help us protect the NHS by claiming your listing today.

We currently have your organisation listed as:
Address: ((ORGANISATION_ADDRESS))
Website: ((ORGANISATION_URL))
Email: ((ORGANISATION_EMAIL))
Phone: ((ORGANISATION_PHONE))
Social Media: ((ORGANISATION_SOCIAL_MEDIA))
Description: ((ORGANISATION_DESCRIPTION))

<a href="((INVITE_URL))">Click here</a> to claim your listing.

The Connect directory has been developed by the NHS to maximise everyone’s access to support for health and wellbeing. Adding a listing is completely free, improves your organisation’s profile, gives you access to new funding opportunities and above all ensures that the people who need your support most are able to access it.
You have the ability to accept as many or as few new clients as you want, or even turn certain services off altogether.

Not your organisation?
Our initial information is not always accurate and nothing you see here is made public until it is confirmed by you. If you want to add details for another organisation <a href="https://admin.connect.nhs.uk/register/">click here</a>.

Having problems?
You can view some <a href="https://connect.nhs.uk/providers">Frequently Asked Questions here</a>.

<a href="https://www.facebook.com/groups/1016800338800042">Click here to join our Users Community</a>! A community space for services listed on NHS Connect. Here you can collaborate with other NHS Connect services, learn more about the ways it can help you and your users. It's also your go-to place to connect with the NHS Connect team about the product/ give feedback/make suggestions.

Many thanks for your help

NHS Connect Team
EOT;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return 'Your organisation is at risk of losing your listing on NHS Connect – it takes just five minutes to confirm your details';
    }
}
