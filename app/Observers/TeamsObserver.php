<?php

namespace App\Observers;

use Throwable;
use App\Models\Membership;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

class TeamsObserver
{
    /**
     * Handle the "created" event.
     *
     * @param Team $team
     */
    public function created(Team $team): void {}

    /**
     * Handle the "updated" event.
     *
     * @param Team $team
     */
    public function updated(Team $team): void {}

    /**
     * Handle the "deleted" event.
     *
     * @param Team $team
     *
     * @throws Throwable
     */
    public function deleted(Team $team): void
    {
        DB::transaction(function () use ($team) {
            // delete all team's permissions
            $team->role->each->delete();
            $team->role->each->delete();

            // delete all team's users' relationships
            Membership::query()->where('team_id', $team->id)->delete();

            // delete team invitation
            $team->teamInvitations->each->delete();

            // TODO: Add websocket events to refresh user UI and add a notification

        });
    }

    /**
     * Handle the "restored" event.
     *
     * @param Team $team
     */
    public function restored(Team $team): void {}
}
