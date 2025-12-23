<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\Devices;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class DevicesController extends Controller
{
    public function index(): InertiaResponse
    {
        return Inertia::render('Devices', [
            //            'devices' => Devices::paginate(),
        ]);
    }
}
