<?php

use App\Http\Controllers\Teams\InviteController;
use App\Http\Controllers\Teams\TeamsController;

Route::get(config('teams.invitations.routes.url'), [InviteController::class, 'inviteAccept'])
    ->middleware([
        config('teams.invitations.routes.middleware'),
        'signed',
    ])
    ->name('teams.invitations.accept');

Route::get('teams/user/invitation/{id}', [InviteController::class, 'createUser'])
    ->middleware(['signed', 'guest'])
    ->name('teams.invitations.create.user');

Route::post('teams/user/invitation/{id}', [InviteController::class, 'store'])
    ->middleware(['guest'])
    ->name('teams.invitations.store.user');

Route::middleware('auth')->prefix('teams')->group(function () {

    Route::prefix('/manage')->group(function () {

        Route::get('/', [TeamsController::class, 'index'])
            ->name('teams.manage.index');

        Route::put('/update/team/{id}/name', [TeamsController::class, 'updateTeamName'])
            ->name('teams.manage.update.team.name');

    });

    Route::get('/getAllTeams', [TeamsController::class, 'getTeams'])
        ->name('teams.getAllTeams');

    Route::get('/getTeamMenu', [TeamsController::class, 'getTeamMenus'])
        ->name('teams.getTeamMenu');

    Route::post('/session/team', [TeamsController::class, 'setCurrentTeam'])
        ->name('teams.switchTeam');

    Route::get('/session/team', [TeamsController::class, 'getCurrentTeam'])
        ->name('teams.getCurrentTeam');

});
