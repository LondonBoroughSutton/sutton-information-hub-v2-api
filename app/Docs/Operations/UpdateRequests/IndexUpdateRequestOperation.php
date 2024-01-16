<?php

namespace App\Docs\Operations\UpdateRequests;

use App\Docs\Parameters\FilterIdParameter;
use App\Docs\Parameters\FilterParameter;
use App\Docs\Parameters\IncludeParameter;
use App\Docs\Parameters\PageParameter;
use App\Docs\Parameters\PerPageParameter;
use App\Docs\Parameters\SortParameter;
use App\Docs\Schemas\PaginationSchema;
use App\Docs\Schemas\UpdateRequest\UpdateRequestSchema;
use App\Docs\Tags\UpdateRequestsTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class IndexUpdateRequestOperation extends Operation
{
    /**
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->action(static::ACTION_GET)
            ->tags(UpdateRequestsTag::create())
            ->summary('List all the update requests')
            ->description(
                <<<'EOT'
**Permission:** `Super Admin`

---

Update requests are returned in descending order of the date they were created.
EOT
            )
            ->parameters(
                PageParameter::create(),
                PerPageParameter::create(),
                FilterIdParameter::create(),
                FilterParameter::create(null, 'service_id')
                    ->description('Comma separated list of service IDs to filter by')
                    ->schema(
                        Schema::array()->items(
                            Schema::string()->format(Schema::FORMAT_UUID)
                        )
                    )
                    ->style(FilterParameter::STYLE_SIMPLE),
                FilterParameter::create(null, 'service_location_id')
                    ->description('Comma separated list of service location IDs to filter by')
                    ->schema(
                        Schema::array()->items(
                            Schema::string()->format(Schema::FORMAT_UUID)
                        )
                    )
                    ->style(FilterParameter::STYLE_SIMPLE),
                FilterParameter::create(null, 'location_id')
                    ->description('Comma separated list of location IDs to filter by')
                    ->schema(
                        Schema::array()->items(
                            Schema::string()->format(Schema::FORMAT_UUID)
                        )
                    )
                    ->style(FilterParameter::STYLE_SIMPLE),
                FilterParameter::create(null, 'organisation_id')
                    ->description('Comma separated list of organisation IDs to filter by')
                    ->schema(
                        Schema::array()->items(
                            Schema::string()->format(Schema::FORMAT_UUID)
                        )
                    )
                    ->style(FilterParameter::STYLE_SIMPLE),
                FilterParameter::create(null, 'entry')
                    ->description(
                        <<<'EOT'
Entry to filter by:
* Service: `name`
* Location: `address_line_1`
* Service location: `name` or `address_line_1` for associated location
* Organisation: `name`
* Organisation sign up form: `name` for organisation
EOT
                    )
                    ->schema(Schema::string()),
                FilterParameter::create(null, 'type')
                    ->description(
                        <<<'EOT'
Type to filter by:
* Service update: `services`
* New Service: `new_service_created_by_global_admin`
* Location: `locations`
* Service location: `service_locations`
* Organisation update: `organisations`
* New Organisation: `new_organisation_created_by_global_admin`
* Organisation sign up form: `organisation_sign_up_form`
* Event update: `organisation_events`
* New Event: `new_organisation_event_created_by_org_admin`
* Page update: `pages`
* New Page: `new_page`
* Referral: `referrals`
EOT
                    )
                    ->schema(Schema::string()),
                IncludeParameter::create(null, ['user']),
                SortParameter::create(null, ['entry','created_at'], '-created_at')
            )
            ->responses(
                Response::ok()->content(
                    MediaType::json()->schema(
                        PaginationSchema::create(null, UpdateRequestSchema::create())
                    )
                )
            );
    }
}
