<?php

namespace App\Http\Resources\Teams;

use App\Actions\Teams\RetrieveCurrentSessionTeam;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin Team */
class TeamsMenuResource extends JsonResource
{
    /**
     * @param Request $request
     *
     * @return array
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'label'        => $this->name,
            'current_team' => Team::query()->where('id', session('current_team_id'))->count() && resolve(RetrieveCurrentSessionTeam::class)->handle()->id === $this->id,
            'default'      => $this->default,
        ];
    }
}
