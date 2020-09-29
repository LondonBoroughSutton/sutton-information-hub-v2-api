<?php

namespace App\Docs\Operations\ServiceLocations;

use App\Docs\Schemas\ResourceSchema;
use App\Docs\Schemas\ServiceLocation\ServiceLocationSchema;
use App\Docs\Schemas\ServiceLocation\UpdateServiceLocationSchema;
use App\Docs\Tags\ServiceLocationsTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;

class UpdateServiceLocationOperation extends Operation
{
    /**
     * @param string|null $objectId
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     * @return static
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->action(static::ACTION_PUT)
            ->tags(ServiceLocationsTag::create())
            ->summary('Update a specific service location')
            ->description('**Permission:** `Service Admin`')
            ->requestBody(
                RequestBody::create()
                    ->required()
                    ->content(
                        MediaType::json()->schema(UpdateServiceLocationSchema::create())
                    )
            )
            ->responses(
                Response::created()->content(
                    MediaType::json()->schema(
                        ResourceSchema::create(null, ServiceLocationSchema::create())
                    )
                )
            );
    }
}
