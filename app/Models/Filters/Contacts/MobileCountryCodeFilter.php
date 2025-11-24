<?php

namespace App\Models\Filters\Contacts;

use App\Concerns\MobileCountryCode;
use Lacodix\LaravelModelFilter\Filters\EnumFilter;

class MobileCountryCodeFilter extends EnumFilter
{
    protected string $field = 'country_code';

    protected string $enum = MobileCountryCode::class;
}
