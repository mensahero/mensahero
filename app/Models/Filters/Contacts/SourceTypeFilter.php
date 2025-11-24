<?php

namespace App\Models\Filters\Contacts;

use App\Concerns\ContactSources;
use Lacodix\LaravelModelFilter\Filters\EnumFilter;

class SourceTypeFilter extends EnumFilter
{
    protected string $field = 'source';

    protected string $enum = ContactSources::class;
}
