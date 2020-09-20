<?php

namespace App\Docs\Paths\PendingOrganisationAdmins;

use App\Docs\Operations\PendingOrganisationAdmins\ConfirmPendingOrganisationAdminOperation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class PendingOrganisationAdminConfirmPath extends PathItem
{
    /**
     * @param string|null $objectId
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     * @return static
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/pending-organisation-admins/{pending_organisation_admin}/confirm')
            ->parameters(
                Parameter::path()
                    ->name('pending_organisation_admin')
                    ->description('The ID of the pending organisation admin')
                    ->required()
                    ->schema(Schema::string())
            )
            ->operations(
                ConfirmPendingOrganisationAdminOperation::create()
            );
    }
}
