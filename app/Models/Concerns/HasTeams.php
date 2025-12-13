<?php

namespace App\Models\Concerns;

use App\Actions\Teams\CreateCurrentSessionTeam;
use App\Actions\Teams\RetrieveCurrentSessionTeam;
use App\Concerns\RolesPermissions;
use App\Models\Membership;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;

trait HasTeams
{
    /**
     * Determine if the given team is the current team.
     *
     * @param Team $team
     *
     * @throws Exception
     *
     * @return bool
     */
    public function isCurrentTeam(Team $team): bool
    {
        return $team->id === resolve(RetrieveCurrentSessionTeam::class)->handle()->id;
    }

    /**
     * @throws Exception
     *
     * @return Team
     */
    public function currentTeam(): Team
    {
        return resolve(RetrieveCurrentSessionTeam::class)->handle();
    }

    /**
     * @param Team $team
     *
     * @throws Exception
     *
     * @return bool
     */
    public function switchTeam(Team $team): bool
    {
        if (! $this->belongsToTeam($team)) {
            return false;
        }

        resolve(CreateCurrentSessionTeam::class)->handle($team);

        return true;
    }

    /**
     * @return Collection
     */
    public function allTeams(): Collection
    {
        return $this->ownedTeams->merge($this->teams)->sortBy('name');
    }

    /**
     * @return HasMany<Team, $this>
     */
    public function ownedTeams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    /**
     * @return BelongsToMany<Team, $this, Pivot>
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, Membership::class)
            ->withPivot('role_id')
            ->withTimestamps()
            ->as('membership');
    }

    /**
     * @return Team|null
     */
    public function defaultTeam(): ?Team
    {
        return $this->ownedTeams->where('default', true)->first();
    }

    /**
     * @param Team $team
     *
     * @return bool
     */
    public function ownsTeam(Team $team): bool
    {
        return $this->id == $team->user_id;
    }

    /**
     * Determine if the user belongs to the given team.
     *
     * @param Team $team
     *
     * @return bool
     */
    public function belongsToTeam(Team $team): bool
    {

        return $this->ownsTeam($team) || $this->teams->contains(fn ($t) => $t->id === $team->id);
    }

    /**
     * Get the role that the user has on the team.
     *
     * @param Team $team
     *
     * @return Role|null
     */
    public function teamRole(Team $team): ?Role
    {

        if (! $this->belongsToTeam($team)) {
            return null;
        }

        if ($team->owner->id === $this->id) {
            return Role::query()->where('name', RolesPermissions::Administrator->id())->first();
        }

        $role = $team->allUsers()
            ->where('id', $this->id)
            ->first()
            ->membership
            ->role_id;

        return $role ? Role::query()->find($role) : null;
    }

    /**
     * Determine if the user has the given role on the given team.
     *
     * @param Team   $team
     * @param string $role
     *
     * @return bool
     */
    public function hasTeamRole(Team $team, string $role): bool
    {
        return $this->belongsToTeam($team) && Role::query()->find($team->users->where(
            'id', $this->id
        )->first()->membership->role)?->key === $role;
    }

    /**
     * Get the user's permissions for the given team.
     *
     * @param Team $team
     *
     * @return Collection|null
     */
    public function teamPermissions(Team $team): ?Collection
    {
        if (! $this->belongsToTeam($team)) {
            return null;
        }

        return $this->teamRole($team)?->permissions;
    }

    /**
     * Determine if the user has the given permission on the given team.
     *
     * @param Team   $team
     * @param string $permission
     *
     * @return bool
     */
    public function hasTeamPermission(Team $team, string $permission): bool
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

        return $permissions && (in_array($permission, (array) $permissions) ||
                in_array('*', (array) $permissions));
    }
}
