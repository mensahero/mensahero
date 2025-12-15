<?php

namespace App\Actions\Teams;

use App\Concerns\TeamSessionKeys;
use App\Models\Team;

class CreateCurrentSessionTeam
{
    /**
     * @param Team $team
     *
     * @return void
     */
    public function handle(Team $team): void
    {
        session([
            TeamSessionKeys::CURRENT_TEAM_ID->key()   => (string) $team->id,
            TeamSessionKeys::CURRENT_TEAM_NAME->key() => (string) $team->name,
            TeamSessionKeys::CURRENT_TEAM->key()      => $team,
        ]);

    }
}
