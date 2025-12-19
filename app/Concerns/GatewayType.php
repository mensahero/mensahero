<?php

namespace App\Concerns;

enum GatewayType: string
{
    case Device = 'device';
    case External = 'external';

}
