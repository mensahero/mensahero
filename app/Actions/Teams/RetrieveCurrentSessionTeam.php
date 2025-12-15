<?php

namespace App\Actions\Teams;

use App\Concerns\TeamSessionKeys;
use App\Models\Team;

class RetrieveCurrentSessionTeam
{
    public function handle(): Team
    {
        return Team::query()->where('id', session(TeamSessionKeys::CURRENT_TEAM_ID->key()))->first();
    }
}
