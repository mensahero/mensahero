<?php

namespace App\Actions\Teams;

use App\Concerns\RolesPermissions;
use App\Models\Team;
use Exception;

class CreateRolePermission
{
    /**
     * @throws Exception
     */
    public function handle(Team $team): void
    {
        // create roles for each teams

        // Create Administrator Role
        $roles = $team->role()->updateOrCreate(attributes: [
            'name' => RolesPermissions::Administrator->id(),
        ]);
        // Create Administrator Permissions
        collect(RolesPermissions::Administrator->permissionSets())->each(fn ($permission) => $roles->permissions()->updateOrCreate(attributes: ['name' => $permission, 'team_id' => $team->id]));

        // Create Standard Role
        $roles = $team->role()->updateOrCreate(attributes: [
            'name' => RolesPermissions::Standard->id(),
        ]);
        // Create Standard Permissions
        collect(RolesPermissions::Standard->permissionSets())->each(fn ($permission) => $roles->permissions()->updateOrCreate(attributes: ['name' => $permission, 'team_id' => $team->id]));

        // Create Lite Role
        $roles = $team->role()->updateOrCreate(attributes: [
            'name' => RolesPermissions::Lite->id(),
        ]);
        // Create Lite Permissions
        collect(RolesPermissions::Lite->permissionSets())->each(fn ($permission) => $roles->permissions()->updateOrCreate(attributes: ['name' => $permission, 'team_id' => $team->id]));

    }
}
