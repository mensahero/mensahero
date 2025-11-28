<?php

namespace App\Actions\Teams;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class CreateTeams
{
    /**
     * @param User  $user
     * @param array $attribute     ['name', 'user_id']
     * @param bool  $markAsDefault
     *
     * @throws Throwable
     *
     * @return Team
     */
    public function handle(User $user, array $attribute, bool $markAsDefault = false): Team
    {
        return DB::transaction(fn () => $user->ownedTeams()->save(Team::query()->forceCreate([
            'user_id' => $user->id,
            'name'    => Str::of($attribute['name'])->trim()->endsWith('Team') ? ucwords((string) $attribute['name']) : ucwords((string) $attribute['name']).' Team',
            'default' => $markAsDefault,
        ])));
    }
}
