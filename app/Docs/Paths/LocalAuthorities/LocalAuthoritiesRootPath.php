<?php

namespace App\Docs\Paths\LocalAuthorities;

use App\Docs\Operations\LocalAuthorities\IndexLocalAuthorityOperation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;

class LocalAuthoritiesRootPath extends PathItem
{
    /**
     * @param string|null $objectId
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     * @return static
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/local-authorities')
            ->operations(
                IndexLocalAuthorityOperation::create()
            );
    }
}
