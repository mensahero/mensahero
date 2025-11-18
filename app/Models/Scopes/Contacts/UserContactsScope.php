<?php

namespace App\Models\Scopes\Contacts;

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
        $builder->where('user_id', auth()->user()->id)->orderBy('created_at', 'desc');
    }
}
