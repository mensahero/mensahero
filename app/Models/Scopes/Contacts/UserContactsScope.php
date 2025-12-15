<?php

namespace App\Models\Scopes\Contacts;

use App\Concerns\TeamSessionKeys;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class UserContactsScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param Builder $builder
     * @param Model   $model
     *
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        // apply only if the authenticated user is on a web session
        if (session()->has(TeamSessionKeys::CURRENT_TEAM_ID->key())) {
            $builder->where('team_id', session(TeamSessionKeys::CURRENT_TEAM_ID->key()));
        }
    }
}
