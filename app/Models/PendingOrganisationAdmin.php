<?php

namespace App\Models;

use App\Models\Mutators\PendingOrganisationAdminMutators;
use App\Models\Relationships\PendingOrganisationAdminRelationships;
use App\Models\Scopes\PendingOrganisationAdminScopes;

class PendingOrganisationAdmin extends Model
{
    use PendingOrganisationAdminMutators;
    use PendingOrganisationAdminRelationships;
    use PendingOrganisationAdminScopes;
}
