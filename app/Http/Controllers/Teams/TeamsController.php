<?php

namespace App\Http\Controllers\Teams;

use App\Actions\Teams\CreateCurrentSessionTeam;
use App\Actions\Teams\CreateTeams;
use App\Actions\Teams\RetrieveCurrentSessionTeam;
use App\Concerns\RolesPermissions;
use App\Events\Team\TeamCreatedEvent;
use App\Events\Team\TeamDeletedEvent;
use App\Events\Team\UpdatedEvent;
use App\Events\Team\UserRemovedEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Team\CreateTeamRequest;
use App\Http\Requests\Team\UpdateTeamMemberRoleRequest;
use App\Http\Requests\Team\UpdateTeamNameRequest;
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
    public function createNewTeam(CreateTeamRequest $request): RedirectResponse
    {

        $user = auth()->user();

        // mark the other owned team as not default
        if ($request->boolean('default')) {
            $user->ownedTeams()->update(['default' => false]);
        }

        $team = resolve(CreateTeams::class)->handle($user, [
            'name'    => Str::ucwords($request->name),
            'default' => $request->boolean('default'),
        ], $request->boolean('default'));

        event(new TeamCreatedEvent($team, $user));

        resolve(CreateCurrentSessionTeam::class)->handle($team);

        InertiaNotification::make()
            ->success()
            ->title('Team Created')
            ->message('The team has been created successfully.')
            ->send();

        return to_route('dashboard');
    }

    /**
     * Delete Team and its resources
     *
     * @param Request $request
     *
     * @throws Exception
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
                ->title('Unauthorized Action')
                ->message('You don\'t have permission to perform this action.')
                ->send();

            return to_route('teams.manage.index');
        }

        // prevent deleting the current team if the user has only one own team
        if (auth()->user()->ownedTeams()->count() === 1) {

            InertiaNotification::make()
                ->error()
                ->title('Unable to delete team')
                ->message('Please create a new team before deleting this one.')
                ->send();

            return to_route('teams.manage.index');
        }

        if (filled(auth()->user()->password)) {
            $request->validate([
                'current_password' => ['required', 'current_password'],
            ]);
        }

        // delete the current team
        $team = resolve(RetrieveCurrentSessionTeam::class)->handle();

        if ($team->default) {
            // make one of the team as default
            $newDefaultTeam = $team->owner->ownedTeams()->inRandomOrder()->first();
            $newDefaultTeam->update(['default' => true]);
            resolve(CreateCurrentSessionTeam::class)->handle($newDefaultTeam);
        } else {
            $randomTeam = $team->owner->allTeams()->random()->first();

            resolve(CreateCurrentSessionTeam::class)->handle($randomTeam);
        }

        broadcast(new TeamDeletedEvent($team->toArray()))->toOthers();

        $team->delete();

        return to_route('dashboard');
    }

    /**
     * @param UpdateTeamNameRequest $request
     * @param string                $id
     *
     * @throws Exception
     *
     * @return RedirectResponse
     */
    public function updateTeamName(UpdateTeamNameRequest $request, string $id): RedirectResponse
    {

        $isOwnedTeam = resolve(RetrieveCurrentSessionTeam::class)->handle()->owner->id === $request->user()->id;
        if (! $isOwnedTeam) {
            InertiaNotification::make()
                ->error()
                ->title('Unauthorized Action')
                ->message('You don\'t have permission to perform this action.')
                ->send();

            return to_route('teams.manage.index');
        }

        $team = Team::query()->findOrFail($id);

        $team->name = $request->name;

        if ($team->default && ! $request->boolean('default')) {
            InertiaNotification::make()
                ->error()
                ->title('Unable to make as none default team')
                ->message('You need to need to choose team and mark as default')
                ->send();

            return to_route('teams.manage.index');
        }

        if (! $team->default && $request->boolean('default')) {
            $team->owner->ownedTeams()->update(['default' => false]);
        }

        $team->default = $request->default;
        $team->save();

        broadcast(new UpdatedEvent($team))->toOthers();

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
        $team = resolve(RetrieveCurrentSessionTeam::class)->handle();
        if (! $request->user()->hasTeamPermission($team, 'team:delete')) {
            InertiaNotification::make()
                ->error()
                ->title('Unauthorized Action')
                ->message('You don\'t have permission to perform this action.')
                ->send();

            return to_route('teams.manage.index');
        }

        if ($id === auth()->user()->id) {
            InertiaNotification::make()
                ->error()
                ->title('Unable to remove yourself from the team')
                ->message('You can\'t remove yourself from the team.')
                ->send();

            return back();
        }

        $request->validate([
            'isMember' => ['required', 'boolean:true,false'],
        ]);

        if ($request->boolean('isMember')) {
            $team = resolve(RetrieveCurrentSessionTeam::class)->handle();
            $user = User::query()->findOrFail($id);
            $team->removeUser($user);
            broadcast(new UserRemovedEvent($team->toArray(), $user))->toOthers();
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
     * @param UpdateTeamMemberRoleRequest $request
     * @param string                      $id
     *
     * @throws Exception
     *
     * @return RedirectResponse
     */
    public function updateTeamMemberRole(UpdateTeamMemberRoleRequest $request, string $id): RedirectResponse
    {

        if (! $request->ensureIsNotOwnRecord() || ! $request->ensureHavePermission()) {
            return to_route('teams.manage.index');
        }

        if ($request->boolean('isMember')) {
            $team = Team::query()->findOrFail($request->team_id);
            $team->users()->updateExistingPivot($id, ['role_id' => $request->role]);

            InertiaNotification::make()
                ->success()
                ->title('Role Updated')
                ->message('The role has been updated successfully.')
                ->send();

            return to_route('teams.manage.index');

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
