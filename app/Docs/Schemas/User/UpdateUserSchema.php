<?php

namespace App\Docs\Schemas\User;

use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class UpdateUserSchema extends Schema
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
            ->required('first_name', 'last_name', 'email', 'phone', 'password', 'roles')
            ->properties(
                Schema::string('first_name'),
                Schema::string('last_name'),
                Schema::string('email'),
                Schema::string('phone')
                    ->nullable(),
                Schema::string('location_id')
                    ->nullable(),
                Schema::string('local_authority_id')
                    ->nullable(),
                Schema::string('password'),
                Schema::array('roles')
                    ->items(
                        RoleSchema::create()
                            ->required('role', 'organisation_id', 'service_id')
                    )
            );
    }
}
