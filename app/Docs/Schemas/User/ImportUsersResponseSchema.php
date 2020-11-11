<?php

namespace App\Docs\Schemas\User;

use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class ImportUsersResponseSchema extends Schema
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
                Schema::integer('imported_row_count'),
                Schema::object('errors')->properties(
                    Schema::array('spreadsheet')->items(
                        Schema::object()->properties(
                            Schema::object('row')->properties(
                                Schema::integer('index'),
                                Schema::string('first_name'),
                                Schema::string('last_name'),
                                Schema::string('email'),
                                Schema::string('phone'),
                                Schema::string('employer_name'),
                                Schema::string('address_line_1'),
                                Schema::string('address_line_2'),
                                Schema::string('address_line_3'),
                                Schema::string('city'),
                                Schema::string('county'),
                                Schema::string('postcode'),
                                Schema::string('country')
                            ),
                            Schema::object('errors')->properties(
                                Schema::string('first_name'),
                                Schema::string('last_name'),
                                Schema::string('email'),
                                Schema::string('phone'),
                                Schema::string('employer_name'),
                                Schema::string('address_line_1'),
                                Schema::string('address_line_2'),
                                Schema::string('address_line_3'),
                                Schema::string('city'),
                                Schema::string('county'),
                                Schema::string('postcode'),
                                Schema::string('country')
                            )
                        )
                    )
                )
            );
    }
}
