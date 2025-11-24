<?php

use App\Http\Controllers\Teams\InviteController;

Route::get(config('teams.invitations.routes.url'), [InviteController::class, 'inviteAccept'])
    ->middleware([
        config('teams.invitations.routes.middleware'),
        'signed',
    ])
    ->name('teams.invitations.accept');
