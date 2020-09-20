<?php

namespace App\Docs\Schemas\OrganisationAdminInvite;

use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class SubmitOrganisationAdminInviteSchema extends Schema
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
            ->required('first_name', 'last_name', 'email', 'phone', 'password')
            ->properties(
                Schema::string('first_name'),
                Schema::string('last_name'),
                Schema::string('email'),
                Schema::string('phone')
                    ->nullable(),
                Schema::string('password')
            );
    }
}
