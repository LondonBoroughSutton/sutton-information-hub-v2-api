<?php

namespace App\Docs\Schemas\LocalAuthority;

use App\Models\LocalAuthority;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class LocalAuthoritySchema extends Schema
{
    /**
     * @param string|null $objectId
     * @return static
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->type(static::TYPE_OBJECT)
            ->properties(
                Schema::string('id')
                    ->format(Schema::FORMAT_UUID),
                Schema::string('name'),
                Schema::string('alt_name')
                    ->nullable(),
                Schema::string('code'),
                Schema::string('region')
                    ->enum(
                        LocalAuthority::REGION_ENGLAND,
                        LocalAuthority::REGION_SCOTLAND,
                        LocalAuthority::REGION_WALES,
                        LocalAuthority::REGION_NORTHERN_IRELAND
                    ),
                Schema::string('created_at')
                    ->format(Schema::FORMAT_DATE_TIME)
                    ->nullable(),
                Schema::string('updated_at')
                    ->format(Schema::FORMAT_DATE_TIME)
                    ->nullable()
            );
    }
}
