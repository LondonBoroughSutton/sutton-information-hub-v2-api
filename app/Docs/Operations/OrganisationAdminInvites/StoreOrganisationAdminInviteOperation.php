<?php

namespace App\Docs\Operations\OrganisationAdminInvites;

use App\Docs\Schemas\AllSchema;
use App\Docs\Schemas\OrganisationAdminInvite\OrganisationAdminInviteSchema;
use App\Docs\Schemas\OrganisationAdminInvite\StoreOrganisationAdminInviteSchema;
use App\Docs\Tags\OrganisationAdminInvitesTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;

class StoreOrganisationAdminInviteOperation extends Operation
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
            ->tags(OrganisationAdminInvitesTag::create())
            ->summary('Create multiple organisation admin invites')
            ->description('**Permission:** `Super Admin`')
            ->requestBody(
                RequestBody::create()
                    ->required()
                    ->content(
                        MediaType::json()->schema(StoreOrganisationAdminInviteSchema::create())
                    )
            )
            ->responses(
                Response::ok()->content(
                    MediaType::json()->schema(
                        AllSchema::create(null, OrganisationAdminInviteSchema::create())
                    )
                )
            );
    }
}
