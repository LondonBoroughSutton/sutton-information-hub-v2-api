<?php

namespace App\Models;

use App\Models\Mutators\LocalAuthorityMutators;
use App\Models\Relationships\LocalAuthorityRelationships;
use App\Models\Scopes\LocalAuthorityScopes;

class LocalAuthority extends Model
{
    use LocalAuthorityMutators;
    use LocalAuthorityRelationships;
    use LocalAuthorityScopes;

    const REGION_ENGLAND = 'England';
    const REGION_SCOTLAND = 'Scotland';
    const REGION_WALES = 'Wales';
    const REGION_NORTHERN_IRELAND = 'Northern Ireland';

    /**
     * Return the Region the Loal Authority is in
     *
     * @return String
     **/
    public function region()
    {
        return [
            'E' => self::REGION_ENGLAND,
            'S' => self::REGION_SCOTLAND,
            'W' => self::REGION_WALES,
            'N' => self::REGION_NORTHERN_IRELAND,
        ][substr(strtoupper($this->code), 0, 1)];
    }
}
