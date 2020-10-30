<?php

namespace App\Models\Relationships;

use App\Models\User;

trait LocalAuthorityRelationships
{
    /**
     * The Users associated with this Local Authority.
     *
     * @param type name
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @author
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
