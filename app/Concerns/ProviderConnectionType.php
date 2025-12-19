<?php

namespace App\Concerns;

enum ProviderConnectionType: string
{
    case oauth2 = 'Oauth';
    case bearer = 'Bearer';
}
