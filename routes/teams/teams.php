<?php

use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Controllers\Teams\TeamsController;
use App\Http\Middleware\HandleInertiaRequests;

Route::prefix('teams')->group(function () {
    Route::get('/user/invitation/{id}', [TeamInvitationController::class, 'createUser'])
        ->middleware(['signed', 'guest'])
        ->name('teams.invitations.create.user');

    Route::get('/invitation/{id}/accept', [TeamInvitationController::class, 'inviteAccept'])
        ->middleware([
            'signed',
        ])
        ->name('teams.invitations.accept');

    Route::post('/user/invitation/{id}', [TeamInvitationController::class, 'store'])
        ->middleware(['guest'])
        ->name('teams.invitations.store.user');

    Route::post('/invitation/{id}/resend', [TeamInvitationController::class, 'resendInvitation'])
        ->middleware(['auth'])
        ->name('teams.invitations.resend');

});

Route::middleware('auth')->prefix('teams')->group(function () {

    Route::prefix('/manage')->group(function () {

        Route::get('/', [TeamsController::class, 'index'])
            ->name('teams.manage.index');

        Route::put('/update/team/{id}/name', [TeamsController::class, 'updateTeamName'])
            ->name('teams.manage.update.team.name');

        Route::post('/invitation/send', [TeamInvitationController::class, 'inviteViaEmail'])
            ->name('teams.manage.invite');

        Route::post('team/create', [TeamsController::class, 'createNewTeam'])
            ->name('teams.manage.create.team');

        Route::patch('/update/team/member/{id}/role', [TeamsController::class, 'updateTeamMemberRole'])
            ->name('teams.manage.update.team.member.role');

        Route::delete('/remove/team/member/{id}', [TeamsController::class, 'removeTeamMember'])
            ->name('teams.manage.remove.team.member');

        Route::delete('destroy/team', [TeamsController::class, 'destroy'])
            ->name('teams.manage.destroy.team');

    });

    Route::get('/getTeamRoles', [TeamsController::class, 'getTeamRoles'])
        ->withoutMiddleware([HandleInertiaRequests::class])
        ->name('teams.getTeamRoles');

    Route::get('/getAllTeams', [TeamsController::class, 'getTeams'])
        ->withoutMiddleware([HandleInertiaRequests::class])
        ->name('teams.getAllTeams');

    Route::get('/getTeamMenu', [TeamsController::class, 'getTeamMenus'])
        ->withoutMiddleware([HandleInertiaRequests::class])
        ->name('teams.getTeamMenu');

    Route::post('/session/team', [TeamsController::class, 'setCurrentTeam'])
        ->withoutMiddleware([HandleInertiaRequests::class])
        ->name('teams.switchTeam');

    Route::get('/session/team', [TeamsController::class, 'getCurrentTeam'])
        ->withoutMiddleware([HandleInertiaRequests::class])
        ->name('teams.getCurrentTeam');

});
