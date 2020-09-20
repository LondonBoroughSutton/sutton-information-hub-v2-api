<?php

namespace App\Docs\Operations\PendingOrganisationAdmins;

use App\Docs\Schemas\ResourceSchema;
use App\Docs\Schemas\User\UserSchema;
use App\Docs\Tags\PendingOrganisationAdminTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;

class ConfirmPendingOrganisationAdminOperation extends Operation
{
    /**
     * @param string|null $objectId
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     * @return static
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->action(static::ACTION_POST)
            ->tags(PendingOrganisationAdminTag::create())
            ->summary('Confirm a pending organisation admin email address')
            ->description('**Permission:** `Open`')
            ->noSecurity()
            ->responses(
                Response::created()->content(
                    MediaType::json()->schema(
                        ResourceSchema::create(null, UserSchema::create())
                    )
                )
            );
    }
}
