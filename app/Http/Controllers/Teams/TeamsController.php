<?php

namespace App\Http\Controllers\Teams;

use App\Actions\Teams\CreateCurrentSessionTeam;
use App\Actions\Teams\CreateRolePermission;
use App\Actions\Teams\CreateTeams;
use App\Actions\Teams\RetrieveCurrentSessionTeam;
use App\Actions\User\CreateUser;
use App\Concerns\RolesPermissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\Teams\InvitationMemberResource;
use App\Http\Resources\Teams\TeamResource;
use App\Http\Resources\Teams\TeamsMenuResource;
use App\Http\Resources\Teams\TeamUsersResource;
use App\Mail\Team\TeamInvitationMail;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\InertiaNotification;
use Exception;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Inertia\Response as InertiaResponse;
use Throwable;

class TeamsController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $teamWithOwner = $team = app(RetrieveCurrentSessionTeam::class)->handle();
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
        ]);
    }

    /**
     *  Invite a user via email.
     *
     * @param Request $request
     *
     * @throws Exception
     *
     * @return void
     */
    public function inviteViaEmail(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'role'  => ['required', 'exists:roles,id'],
        ]);

        $team = app(RetrieveCurrentSessionTeam::class)->handle();
        $searchInvite = TeamInvitation::query()->where('email', $request->email)->first();

        if ($searchInvite || $team->hasUserWithEmail($request->email)) {
            // resend invitation
            $actionUrl = URL::signedRoute('teams.invitations.accept', [
                'id' => $searchInvite->id,
            ]);

            Mail::to($request->email)->queue(new TeamInvitationMail($actionUrl, $searchInvite));
        }

        $invitation = $team->teamInvitations()->create([
            'email'   => $request->email,
            'role_id' => $request->role,
        ]);

        $actionUrl = URL::signedRoute('teams.invitations.accept', [
            'id' => $invitation->id,
        ]);

        Mail::to($request->email)->queue(new TeamInvitationMail($actionUrl, $invitation));

    }

    public function resendInvitation(string $id)
    {
        $invitation = TeamInvitation::query()->findOrFail($id);
        $actionUrl = URL::signedRoute('teams.invitations.accept', [
            'id' => $id,
        ]);
        Mail::to($invitation->email)->queue(new TeamInvitationMail($actionUrl, $invitation));

        InertiaNotification::make()
            ->success()
            ->title('Invitation resent')
            ->message('The invitation has been resent successfully.')
            ->send();

        return back();
    }

    /**
     *  Accept the invitation.
     *
     * @param Request $request
     * @param string  $id
     *
     * @throws Exception
     *
     * @return JsonResponse|RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function inviteAccept(Request $request, string $id)
    {
        $invitation = TeamInvitation::query()->find($id);

        if (! $invitation) {
            return Inertia::render('Exception', [
                'redirect' => URL::route('dashboard'),
                'error'    => [
                    'statusCode'    => 403,
                    'statusMessage' => 403,
                    'message'       => 'The invitation was not found or it has been already accepted or expired.',
                ],
            ])
                ->toResponse($request)
                ->setStatusCode(403);
        }

        if ($invitation->team->hasUserWithEmail($invitation->email)) {
            $user = User::query()->where('email', $invitation->email)->firstOrFail();
            $invitation->team->users()->attach($user, ['role_id' => $request->role]);

            InertiaNotification::make()
                ->success()
                ->title('Invitation accepted')
                ->message(__('You have accepted the invitation to join the :team team.', ['team' => $invitation->team->name]))
                ->send();

            return to_route('dashboard');
        }

        return to_route('teams.invitations.create.user', [
            'id' => $invitation->id,
        ]);

    }

    /**
     * Create a new user page from invitation.
     *
     *
     * @param string $id
     *
     * @return Response
     */
    public function createUser(string $id): InertiaResponse
    {
        $invitation = TeamInvitation::query()->find($id);

        return Inertia::render('RegisterUserInvitation', [
            'email'        => $invitation->email,
            'invitationId' => $invitation->id,
        ]);

    }

    /**
     * Accept the invitation and create the user.
     *
     *
     * @param RegisterRequest $request
     * @param string          $id
     *
     * @throws Throwable
     *
     * @return RedirectResponse
     */
    public function store(RegisterRequest $request, string $id): RedirectResponse
    {

        $invitation = TeamInvitation::query()->findOrFail($id);

        // Get the team from invitation
        $teamInvitation = $invitation->team;

        $user = app(CreateUser::class)->handle($request->validated());

        // Create a personal team for the user and it will the default team
        $personalTeam = app(CreateTeams::class)->handle(
            user: $user,
            attribute: [
                'name'    => Str::possessive(Str::of($user->name)->trim()->explode(' ')->first()),
                'user_id' => $user->id,
            ], markAsDefault: true);

        app(CreateRolePermission::class)->handle($personalTeam);

        event(new Registered($user));

        Auth::login($user);

        $request->session()->regenerate();

        app(CreateCurrentSessionTeam::class)->handle($personalTeam);

        // Accept the invitation
        $teamInvitation->inviteAccept($invitation->id);

        InertiaNotification::make()
            ->success()
            ->title('Invitation accepted')
            ->message(__('You have accepted the invitation to join the :team team.', ['team' => $teamInvitation->name]))
            ->send();

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
            $team = app(RetrieveCurrentSessionTeam::class)->handle();
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
            'team_id' => app(RetrieveCurrentSessionTeam::class)->handle()->id,
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
        $team = app(RetrieveCurrentSessionTeam::class)->handle();

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
            'current_team' => app(RetrieveCurrentSessionTeam::class)->handle(),
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
