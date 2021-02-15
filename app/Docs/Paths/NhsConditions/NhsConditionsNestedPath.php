<?php

namespace App\Docs\Paths\NhsConditions;

use App\Docs\Operations\NhsConditions\ShowNhsConditionOperation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;

class NhsConditionsNestedPath extends PathItem
{
    /**
     * @param string|null $objectId
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     * @return static
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/nhs-conditions/{slug}')
            ->operations(
                ShowNhsConditionOperation::create()
                    ->action(ShowNhsConditionOperation::ACTION_GET)
                    ->description(
                        <<<'EOT'
A proxy to the NHS Conditions API.

_This is needed as there is a CORS issue for redirects._
EOT
                    )
            );
    }
}
