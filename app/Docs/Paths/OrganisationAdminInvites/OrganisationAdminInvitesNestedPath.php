<?php

namespace App\Docs\Paths\OrganisationAdminInvites;

use App\Docs\Operations\OrganisationAdminInvites\ShowOrganisationAdminInviteOperation;
use App\Docs\Operations\OrganisationAdminInvites\StoreOrganisationAdminInviteOperation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class OrganisationAdminInvitesNestedPath extends PathItem
{
    /**
     * @param string|null $objectId
     * @return static
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/organisation-admin-invites/{organisation_admin_invite}')
            ->parameters(
                Parameter::path()
                    ->name('organisation_admin_invite')
                    ->description('The ID of the organisation admin invite')
                    ->required()
                    ->schema(Schema::string())
            )
            ->operations(
                StoreOrganisationAdminInviteOperation::create(),
                ShowOrganisationAdminInviteOperation::create()
            );
    }
}
