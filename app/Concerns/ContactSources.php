<?php

namespace App\Concerns;

enum ContactSources: string
{
    case Phone = 'Phone';
    case CRM = 'CRM';
}
