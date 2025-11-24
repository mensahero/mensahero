<?php

namespace App\Models\Concerns;

use App\Models\Team;
use Jurager\Teams\Traits\HasTeams as BaseHasTeams;

trait HasTeams
{
    use BaseHasTeams;

    public function markAsDefaultTeam(Team $team): void
    {
        $team->default = true;
        $team->save();
    }
}
