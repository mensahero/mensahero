<?php

namespace App\Http\Controllers\Teams;

use App\Actions\Teams\CreateCurrentSessionTeam;
use App\Actions\Teams\CreateRolePermission;
use App\Actions\Teams\CreateTeams;
use App\Actions\Teams\RetrieveCurrentSessionTeam;
use App\Concerns\RolesPermissions;
use App\Http\Controllers\Controller;
use App\Http\Resources\Teams\InvitationMemberResource;
use App\Http\Resources\Teams\TeamResource;
use App\Http\Resources\Teams\TeamsMenuResource;
use App\Http\Resources\Teams\TeamUsersResource;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\InertiaNotification;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Throwable;

class TeamsController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $teamWithOwner = $team = resolve(RetrieveCurrentSessionTeam::class)->handle();
        $teamWithOwner->load(['owner']);

        return Inertia::render('Teams', [
            'team'                => TeamResource::make($teamWithOwner),
            'members'             => [
                'invited' => InvitationMemberResource::collection($team->teamInvitations),
                'members' => TeamUsersResource::collection($team->users),
            ],
            'roles_permissions'   => collect($team->role)->map(fn ($role) => [
                'uuid'        => $role->id,
                'label'       => RolesPermissions::tryFrom($role->name)->label(),
                'description' => RolesPermissions::tryFrom($role->name)->description(),
            ]),
            'deletePasswordRequired' => filled($request->user()->password),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function createNewTeam(Request $request): RedirectResponse
    {
        $request->validate([
            'name'    => ['required', 'string', 'max:255', 'unique:teams,name'],
            'default' => ['required', 'boolean:true,false'],
        ]);

        $user = auth()->user();
        $team = resolve(CreateTeams::class)->handle($user, [
            'name'    => Str::ucwords($request->name),
            'default' => $request->boolean('default'),
        ], $request->boolean('default'));
        resolve(CreateRolePermission::class)->handle($team);

        resolve(CreateCurrentSessionTeam::class)->handle($team);

        return to_route('dashboard');
    }

    /**
     * Delete Team and its resources
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function destroy(Request $request): RedirectResponse
    {
        // check if the user is deleting his owned team or not
        $isOwnedTeam = resolve(RetrieveCurrentSessionTeam::class)->handle()->owner->id === $request->user()->id;
        if (! $isOwnedTeam) {
            InertiaNotification::make()
                ->error()
                ->title('Team Deletion Error')
                ->message('You can not delete a team that you do not own.')
                ->send();

            return back();
        }

        // delete the current team
        $team = resolve(RetrieveCurrentSessionTeam::class)->handle();

        return to_route('dashboard');
    }

    /**
     * @param Request $request
     * @param string  $id
     *
     * @throws Exception
     *
     * @return RedirectResponse
     */
    public function updateTeamName(Request $request, string $id): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique(Team::class, 'name')->ignore($id)],
        ]);

        $team = Team::query()->findOrFail($id);

        $team->name = $request->name;
        $team->save();

        InertiaNotification::make()
            ->success()
            ->title('Team Name Updated')
            ->message('The team name has been updated successfully.')
            ->send();

        return to_route('teams.manage.index');
    }

    /**
     * Remove a team member from the team or revoke the invitation.
     *
     * @param Request $request
     * @param string  $id
     *
     * @throws Exception
     *
     * @return RedirectResponse
     */
    public function removeTeamMember(Request $request, string $id): RedirectResponse
    {
        $request->validate([
            'isMember' => ['required', 'boolean:true,false'],
        ]);

        if ($request->boolean('isMember')) {
            $team = resolve(RetrieveCurrentSessionTeam::class)->handle();
            $user = User::query()->findOrFail($id);
            $team->removeUser($user);
        } else {
            $invitation = TeamInvitation::query()->findOrFail($id);
            $invitation->delete();
        }

        InertiaNotification::make()
            ->success()
            ->title(__(':type :removed', ['type' => $request->boolean('isMember') ? 'Member' : 'Invitation', 'removed' => $request->boolean('isMember') ? 'Removed' : 'Revoked']))
            ->message(__(':type has been :removed successfully.', ['type' => $request->boolean('isMember') ? 'Member' : 'Invitation', 'removed' => $request->boolean('isMember') ? 'removed' : 'revoked']))
            ->send();

        return to_route('teams.manage.index');
    }

    /**
     * @param Request $request
     * @param string  $id
     *
     * @throws Exception
     *
     * @return RedirectResponse
     */
    public function updateTeamMemberRole(Request $request, string $id): RedirectResponse
    {
        $request->merge([
            'team_id' => resolve(RetrieveCurrentSessionTeam::class)->handle()->id,
        ]);

        $request->validate([
            'role'           => ['required', 'exists:roles,id'],
            'team_id'        => ['required', 'exists:teams,id'],
            'isMember'       => ['required', 'boolean:true,false'],
        ]);

        if ($request->boolean('isMember')) {
            $team = Team::query()->findOrFail($request->team_id);
            $team->users()->updateExistingPivot($id, ['role_id' => $request->role]);
        }

        $invitation = TeamInvitation::query()->findOrFail($id);
        $invitation->forceFill([
            'role_id' => $request->role,
        ]);
        $invitation->save();

        InertiaNotification::make()
            ->success()
            ->title('Role Updated')
            ->message('The role has been updated successfully.')
            ->send();

        return to_route('teams.manage.index');

    }

    public function getTeamRoles(): JsonResponse
    {
        $team = resolve(RetrieveCurrentSessionTeam::class)->handle();

        return response()->json([
            'roles' => collect($team->role)->map(fn ($role) => [
                'uuid'        => $role->id,
                'label'       => RolesPermissions::tryFrom($role->name)->label(),
                'description' => RolesPermissions::tryFrom($role->name)->description(),
            ]),
        ]);
    }

    public function getTeams(): JsonResponse
    {
        $user = auth()->user();

        $teams = $user->teams();

        return response()->json([
            'teams' => $teams,
        ]);
    }

    public function getTeamMenus(): JsonResponse
    {
        $user = auth()->user();

        $teams = $user->allTeams();

        return response()->json(TeamsMenuResource::collection($teams));
    }

    /**
     * @throws Exception
     */
    public function getCurrentTeam(): JsonResponse
    {
        return response()->json([
            'current_team' => resolve(RetrieveCurrentSessionTeam::class)->handle(),
        ]);
    }

    public function setCurrentTeam(Request $request): JsonResponse
    {
        $request->validate([
            'team' => ['required', 'exists:teams,id'],
        ]);

        $team = Team::query()->findOrFail($request->team);
        if (! $team) {
            ValidationException::withMessages([
                'team' => 'The team does not exist',
            ]);
        }

        $request->user()->switchTeam($team);

        return response()->json([
            'message' => 'Team switched successfully',
        ]);

    }
}
