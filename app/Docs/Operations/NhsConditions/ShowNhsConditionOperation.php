<?php

namespace App\Docs\Operations\NhsConditions;

use App\Docs\Tags\NhsConditionsTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class ShowNhsConditionOperation extends Operation
{
    /**
     * @param string|null $objectId
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     * @return static
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->action(static::ACTION_GET)
            ->tags(NhsConditionsTag::create())
            ->summary('Proxy to the NHS Conditions API')
            ->description('**Permission:** `Guest`')
            ->parameters(
                Parameter::query()
                    ->name('slug')
                    ->schema(Schema::string())
            )
            ->responses(
                Response::ok()->content(
                    MediaType::json()
                )
            );
    }
}
