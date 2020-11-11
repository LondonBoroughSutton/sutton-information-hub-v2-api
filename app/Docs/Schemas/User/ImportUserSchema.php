<?php

namespace App\Docs\Schemas\User;

use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class ImportUserSchema extends Schema
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
                'spreadsheet'
            )
            ->properties(
                Schema::string('local_authority_id')
                    ->format(static::FORMAT_UUID)
                    ->description('UUID of an exisiting Local Authority'),
                Schema::string('spreadsheet')
                    ->format(static::FORMAT_BINARY)
                    ->description('Base64 encoded string of an Excel compatible spreadsheet'),
                Schema::array('roles')
                    ->items(
                        RoleSchema::create()
                            ->required('role', 'organisation_id', 'service_id')
                    )
            );
    }
}
