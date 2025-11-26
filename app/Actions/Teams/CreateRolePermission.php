<?php

namespace App\Actions\Teams;

use Exception;
use App\Concerns\RolesPermissions;
use App\Models\Team;

class CreateRolePermission
{

    /**
     * @throws Exception
     */
    public function handle(Team $team): void
    {
        $team->addRole(RolesPermissions::Administrator->id(), RolesPermissions::Administrator->permissionSets());
        $team->addRole(RolesPermissions::Standard->id(), RolesPermissions::Standard->permissionSets());
        $team->addRole(RolesPermissions::Lite->id(), RolesPermissions::Lite->permissionSets());
    }

}
