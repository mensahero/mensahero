<?php

use App\Http\Controllers\Modules\DevicesController;

Route::prefix('/devices')->middleware(['auth'])->group(function () {

    Route::get('/', [DevicesController::class, 'index'])
        ->name('devices.index');

});
