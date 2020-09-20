<?php

namespace App\Docs\Paths\OrganisationAdminInvites;

use App\Docs\Operations\OrganisationAdminInvites\SubmitOrganisationAdminInviteOperation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class OrganisationAdminInvitesSubmitPath extends PathItem
{
    /**
     * @param string|null $objectId
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     * @return static
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/organisation-admin-invites/{organisation_admin_invite}/submit')
            ->parameters(
                Parameter::path()
                    ->name('organisation_admin_invite')
                    ->description('The ID of the organisation admin invite')
                    ->required()
                    ->schema(Schema::string())
            )
            ->operations(
                SubmitOrganisationAdminInviteOperation::create()
            );
    }
}
