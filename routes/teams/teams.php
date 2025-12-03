<?php

use App\Http\Controllers\Teams\TeamsController;

Route::prefix('teams')->group(function () {
    Route::get('/user/invitation/{id}', [TeamsController::class, 'createUser'])
        ->middleware(['signed', 'guest'])
        ->name('teams.invitations.create.user');

    Route::get('/invitation/{id}/accept', [TeamsController::class, 'inviteAccept'])
        ->middleware([
            'signed',
        ])
        ->name('teams.invitations.accept');

    Route::post('/user/invitation/{id}', [TeamsController::class, 'store'])
        ->middleware(['guest'])
        ->name('teams.invitations.store.user');

    Route::post('/invitation/{id}/resend', [TeamsController::class, 'resendInvitation'])
        ->middleware(['auth'])
        ->name('teams.invitations.resend');

});

Route::middleware('auth')->prefix('teams')->group(function () {

    Route::prefix('/manage')->group(function () {

        Route::get('/', [TeamsController::class, 'index'])
            ->name('teams.manage.index');

        Route::put('/update/team/{id}/name', [TeamsController::class, 'updateTeamName'])
            ->name('teams.manage.update.team.name');

        Route::post('/invitation/send', [TeamsController::class, 'inviteViaEmail'])
            ->name('teams.manage.invite');

        Route::post('team/create', [TeamsController::class, 'createNewTeam'])
            ->name('teams.manage.create.team');

        Route::patch('/update/team/member/{id}/role', [TeamsController::class, 'updateTeamMemberRole'])
            ->name('teams.manage.update.team.member.role');

        Route::delete('/remove/team/member/{id}', [TeamsController::class, 'removeTeamMember'])
            ->name('teams.manage.remove.team.member');

    });

    Route::get('/getTeamRoles', [TeamsController::class, 'getTeamRoles'])
        ->name('teams.getTeamRoles');

    Route::get('/getAllTeams', [TeamsController::class, 'getTeams'])
        ->name('teams.getAllTeams');

    Route::get('/getTeamMenu', [TeamsController::class, 'getTeamMenus'])
        ->name('teams.getTeamMenu');

    Route::post('/session/team', [TeamsController::class, 'setCurrentTeam'])
        ->name('teams.switchTeam');

    Route::get('/session/team', [TeamsController::class, 'getCurrentTeam'])
        ->name('teams.getCurrentTeam');

});
