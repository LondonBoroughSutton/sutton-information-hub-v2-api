<?php

namespace App\Docs\Operations\Services;

use App\Docs\Schemas\ResourceSchema;
use App\Docs\Schemas\Service\ServiceSchema;
use App\Docs\Schemas\Service\UpdateServiceSchema;
use App\Docs\Tags\ServicesTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;

class UpdateServiceOperation extends Operation
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
            ->tags(ServicesTag::create())
            ->summary('Update a specific service')
            ->description(
                <<<'EOT'
**Permission:** `Service Admin`
- Can update a service location but not it's taxonomies

**Permission:** `Global Admin`
- Can update a service location
EOT
            )
            ->requestBody(
                RequestBody::create()
                    ->required()
                    ->content(
                        MediaType::json()->schema(UpdateServiceSchema::create())
                    )
            )
            ->responses(
                Response::created()->content(
                    MediaType::json()->schema(
                        ResourceSchema::create(null, ServiceSchema::create())
                    )
                )
            );
    }
}
