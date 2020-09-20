<?php

namespace App\Docs\Schemas\OrganisationAdminInvite;

use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class StoreOrganisationAdminInviteSchema extends Schema
{
    /**
     * @param string|null $objectId
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     * @return static
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->type(static::TYPE_OBJECT)
            ->required(
                'organisations'
            )
            ->properties(
                Schema::array('organisations')->items(
                    Schema::object()
                        ->required('organisation_id')
                        ->properties(
                            Schema::string('organisation_id')->format(Schema::FORMAT_UUID),
                            Schema::boolean('use_email')
                        )
                )
            );
    }
}
