<?php

namespace App\Docs\Operations\LocalAuthorities;

use App\Docs\Schemas\LocalAuthority\LocalAuthoritySchema;
use App\Docs\Tags\LocalAuthoritiesTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class IndexLocalAuthorityOperation extends Operation
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
            ->tags(LocalAuthoritiesTag::create())
            ->summary('List all the Local Authorities')
            ->description('**Permission:** `Open`')
            ->noSecurity()
            ->responses(
                Response::ok()->content(
                    MediaType::json()->schema(
                        Schema::array('data')->items(LocalAuthoritySchema::create())
                    )
                )
            );
    }
}
