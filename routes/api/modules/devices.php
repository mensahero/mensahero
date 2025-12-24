<?php

use App\Http\Controllers\Api\Module\Devices\DevicesController;

Route::prefix('/devices')->middleware('auth:users-api')->group(function () {

    Route::patch('/ping/online', [DevicesController::class, 'ping'])->name('gateway.devices.ping.online');

    Route::prefix('gateway')->group(function () {

        Route::post('/', [DevicesController::class, 'create'])->name('gateway.devices.create');

    });

});
