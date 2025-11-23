<?php

use App\Http\Controllers\Modules\ContactsController;

Route::middleware('auth')->prefix('contacts')->group(function (): void {

    Route::get('/enums', [ContactsController::class, 'enums'])
        ->name('contacts.enums');

    Route::get('/', [ContactsController::class, 'create'])
        ->name('contacts.create');
    Route::post('/', [ContactsController::class, 'store'])
        ->name('contacts.store');
    Route::put('/{id}', [ContactsController::class, 'update'])
        ->name('contacts.update');
    Route::post('/delete', [ContactsController::class, 'destroy'])
        ->name('contacts.destroy');
});
