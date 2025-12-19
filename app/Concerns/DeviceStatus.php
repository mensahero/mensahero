<?php

namespace App\Concerns;

enum DeviceStatus: string
{
    case Online = 'online';
    case Offline = 'offline';

}
