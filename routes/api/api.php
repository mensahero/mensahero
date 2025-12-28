<?php

use App\Facades\Api;

require __DIR__.'/auth/auth.php';
require __DIR__.'/profile/user.php';
require __DIR__.'/modules/devices.php';


Route::get('/health', function () {
    try {
        return 'OK';
    } catch (\Throwable $th) {
        return $th;
    }
});

Route::fallback(fn () => Api::error('Path not found', 404));
