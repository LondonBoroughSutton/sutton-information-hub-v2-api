<?php

namespace App\Models;

use App\Models\Mutators\OrganisationAdminInviteMutators;
use App\Models\Relationships\OrganisationAdminInviteRelationships;
use App\Models\Scopes\OrganisationAdminInviteScopes;

class OrganisationAdminInvite extends Model
{
    use OrganisationAdminInviteMutators;
    use OrganisationAdminInviteRelationships;
    use OrganisationAdminInviteScopes;
}
