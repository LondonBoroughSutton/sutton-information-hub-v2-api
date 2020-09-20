<?php

namespace App\Docs\Schemas\PendingOrganisationAdmin;

use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class PendingOrganisationAdminSchema extends Schema
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
                Schema::string('id')->format(Schema::FORMAT_UUID),
                Schema::string('organisation_id')->format(Schema::FORMAT_UUID),
                Schema::string('first_name'),
                Schema::string('last_name'),
                Schema::string('email'),
                Schema::string('phone')->nullable(),
                Schema::string('created_at')->format(Schema::FORMAT_DATE_TIME)->nullable(),
                Schema::string('updated_at')->format(Schema::FORMAT_DATE_TIME)->nullable()
            );
    }
}
