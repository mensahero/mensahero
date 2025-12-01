<?php

namespace App\Models\Concerns;

use App\Actions\Teams\CreateCurrentSessionTeam;
use App\Actions\Teams\RetrieveCurrentSessionTeam;
use App\Models\Membership;
use App\Models\Role;
use App\Models\Team;
use Exception;
use Laravel\Sanctum\HasApiTokens;

trait HasTeams
{
    /**
     * Determine if the given team is the current team.
     *
     * @param mixed $team
     *
     * @throws Exception
     *
     * @return bool
     */
    public function isCurrentTeam(Team $team): bool
    {
        return $team->id === app(RetrieveCurrentSessionTeam::class)->handle()->id;
    }

    public function currentTeam()
    {
        return app(RetrieveCurrentSessionTeam::class)->handle();
    }

    public function switchTeam(Team $team): bool
    {
        if (! $this->belongsToTeam($team)) {
            return false;
        }

        app(CreateCurrentSessionTeam::class)->handle($team);

        return true;
    }

    public function allTeams()
    {
        return $this->ownedTeams->merge($this->teams)->sortBy('name');
    }

    public function ownedTeams()
    {
        return $this->hasMany(Team::class);
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, Membership::class)
            ->withPivot('role_id')
            ->withTimestamps()
            ->as('membership');
    }

    public function defaultTeam()
    {
        return $this->ownedTeams->where('default', true)->first();
    }

    public function ownsTeam($team): bool
    {
        if (is_null($team)) {
            return false;
        }

        return $this->id == $team->user_id;
    }

    /**
     * Determine if the user belongs to the given team.
     *
     * @param mixed $team
     *
     * @return bool
     */
    public function belongsToTeam(Team $team)
    {

        return $this->ownsTeam($team) || $this->teams->contains(fn ($t) => $t->id === $team->id);
    }

    /**
     * Get the role that the user has on the team.
     *
     * @param mixed $team
     *
     * @return mixed
     */
    public function teamRole(Team $team): mixed
    {

        if (! $this->belongsToTeam($team)) {
            return null;
        }

        $role = $team->users
            ->where('id', $this->id)
            ->first()
            ->membership
            ->role;

        return $role ? Role::query()->find($role) : null;
    }

    /**
     * Determine if the user has the given role on the given team.
     *
     * @param mixed  $team
     * @param string $role
     *
     * @return bool
     */
    public function hasTeamRole(Team $team, string $role)
    {
        return $this->belongsToTeam($team) && Role::query()->find($team->users->where(
            'id', $this->id
        )->first()->membership->role)?->key === $role;
    }

    /**
     * Get the user's permissions for the given team.
     *
     * @param mixed $team
     *
     * @return array
     */
    public function teamPermissions(Team $team): array
    {
        if (! $this->belongsToTeam($team)) {
            return [];
        }

        return (array) $this->teamRole($team)?->permissions;
    }

    /**
     * Determine if the user has the given permission on the given team.
     *
     * @param mixed  $team
     * @param string $permission
     *
     * @return bool
     */
    public function hasTeamPermission(Team $team, string $permission)
    {
        if ($this->ownsTeam($team)) {
            return true;
        }

        if (! $this->belongsToTeam($team)) {
            return false;
        }

        if (in_array(HasApiTokens::class, class_uses_recursive($this)) &&
            ! $this->tokenCan($permission) &&
            $this->currentAccessToken() !== null) {
            return false;
        }

        $permissions = $this->teamPermissions($team);

        return in_array($permission, $permissions) ||
            in_array('*', $permissions);
    }
}
