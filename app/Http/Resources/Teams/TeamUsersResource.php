<?php

namespace App\Http\Resources\Teams;

use App\Actions\Teams\RetrieveCurrentSessionTeam;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin User */
class TeamUsersResource extends JsonResource
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
            'id'                => $this->id,
            'name'              => $this->name,
            'email'             => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'created_at'        => $this->membership->created_at ?? $this->created_at,
            'updated_at'        => $this->updated_at,
            'is_owner'          => $this->id === $request->user()?->currentTeam()?->user_id,
            'role_id'           => $this->teamRole(resolve(RetrieveCurrentSessionTeam::class)->handle())?->id,
        ];
    }
}
