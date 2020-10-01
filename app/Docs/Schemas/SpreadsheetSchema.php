<?php

namespace App\Docs\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class SpreadsheetSchema extends Schema
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
                            Schema::array('row')->items(
                                Schema::string()
                            ),
                            Schema::array('errors')->items(
                                Schema::string()
                            )
                        )
                    )
                )
            );
    }
}
