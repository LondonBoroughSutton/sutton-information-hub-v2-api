<?php

namespace App\Docs\Operations\Organisations;

use App\Docs\Parameters\FilterIdParameter;
use App\Docs\Parameters\FilterParameter;
use App\Docs\Parameters\PageParameter;
use App\Docs\Parameters\PerPageParameter;
use App\Docs\Parameters\SortParameter;
use App\Docs\Schemas\Organisation\OrganisationSchema;
use App\Docs\Schemas\PaginationSchema;
use App\Docs\Tags\OrganisationsTag;
use App\Models\Organisation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class IndexOrganisationOperation extends Operation
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
            ->tags(OrganisationsTag::create())
            ->summary('List all the organisations')
            ->description('**Permission:** `Open`')
            ->noSecurity()
            ->parameters(
                PageParameter::create(),
                PerPageParameter::create(),
                FilterIdParameter::create(),
                FilterParameter::create(null, 'name')
                    ->description('Name to filter by')
                    ->schema(Schema::string()),
                FilterParameter::create(null, 'is_admin')
                    ->description('Filter organisations to only ones they have admin permissions for')
                    ->schema(Schema::boolean()),
                FilterParameter::create(null, 'has_permission')
                    ->description('Filter organisations to only ones they have permissions for')
                    ->schema(Schema::boolean()),
                FilterParameter::create(null, 'has_email')
                    ->description('Filter out organisations that have no email')
                    ->schema(Schema::boolean()),
                FilterParameter::create(null, 'has_social_medias')
                    ->description('Filter out organisations that have no social medias')
                    ->schema(Schema::boolean()),
                FilterParameter::create(null, 'has_phone')
                    ->description('Filter out organisations that have no phone')
                    ->schema(Schema::boolean()),
                FilterParameter::create(null, 'has_services')
                    ->description('Filter out organisations that have no services')
                    ->schema(Schema::boolean()),
                FilterParameter::create(null, 'has_admin_invite_status')
                    ->description('Filter organisations to those with a given status of Admin invite')
                    ->schema(Schema::string()
                    ->enum(
                                Organisation::ADMIN_INVITE_STATUS_NONE,
                                Organisation::ADMIN_INVITE_STATUS_INVITED,
                                Organisation::ADMIN_INVITE_STATUS_PENDING,
                                Organisation::ADMIN_INVITE_STATUS_CONFIRMED
                            )),
                SortParameter::create(null, ['name'], 'name')
            )
            ->responses(
                Response::ok()->content(
                    MediaType::json()->schema(
                        PaginationSchema::create(null, OrganisationSchema::create())
                    )
                )
            );
    }
}
