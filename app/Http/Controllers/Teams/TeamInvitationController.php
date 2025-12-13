<?php

namespace App\Http\Controllers\Teams;

use App\Actions\Teams\CreateCurrentSessionTeam;
use App\Actions\Teams\CreateRolePermission;
use App\Actions\Teams\CreateTeams;
use App\Actions\Teams\RetrieveCurrentSessionTeam;
use App\Actions\User\CreateUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Mail\Team\TeamInvitationMail;
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
use Inertia\Inertia;
use Inertia\Response;
use Inertia\Response as InertiaResponse;
use Throwable;

class TeamInvitationController extends Controller
{
    /**
     *  Invite a user via email.
     *
     * @param Request $request
     *
     * @throws Exception
     *
     * @return RedirectResponse
     */
    public function inviteViaEmail(Request $request): RedirectResponse
    {
        $team = resolve(RetrieveCurrentSessionTeam::class)->handle();
        if (! $request->user()->hasTeamPermission($team, 'team:invite')) {
            InertiaNotification::make()
                ->error()
                ->title('Unauthorized Action')
                ->message('You don\'t have permission to perform this action.')
                ->send();

            return back();
        }

        $request->validate([
            'email' => ['required', 'email'],
            'role'  => ['required', 'exists:roles,id'],
        ]);

        $searchInvite = TeamInvitation::query()->where('email', $request->email)->first();

        if ($searchInvite || $team->hasUserWithEmail($request->email)) {
            // resend invitation
            $actionUrl = URL::temporarySignedRoute('teams.invitations.accept',
                now()->addMinutes(15),
                [
                    'id' => $searchInvite->id,
                ]);

            Mail::to($request->email)->queue(new TeamInvitationMail($actionUrl, $searchInvite));
        }

        $invitation = $team->teamInvitations()->create([
            'email'   => $request->email,
            'role_id' => $request->role,
        ]);

        $actionUrl = URL::temporarySignedRoute('teams.invitations.accept', now()->addMinutes(15), [
            'id' => $invitation->id,
        ]);

        InertiaNotification::make()
            ->success()
            ->title('Invitation Sent')
            ->message('The invitation has been sent successfully.')
            ->send();

        Mail::to($request->email)->queue(new TeamInvitationMail($actionUrl, $invitation));

        return to_route('teams.manage.index');

    }

    public function resendInvitation(Request $request, string $id)
    {
        $team = resolve(RetrieveCurrentSessionTeam::class)->handle();
        if (! $request->user()->hasTeamPermission($team, 'team:invite')) {
            InertiaNotification::make()
                ->error()
                ->title('Unauthorized Action')
                ->message('You don\'t have permission to perform this action.')
                ->send();

            return back();
        }

        $request->validate([
            'email' => ['required', 'email'],
            'role'  => ['required', 'exists:roles,id'],
        ]);

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
            $invitation->team->users()->attach($user, ['role_id' => $invitation->role_id]);
            // delete the invitation
            $invitation->delete();

            InertiaNotification::make()
                ->success()
                ->title('Invitation accepted')
                ->message(__('Great! You have accepted the invitation to join the :team team.', ['team' => $invitation->team->name]))
                ->send();

            return to_route('dashboard');
        }

        if ($user = User::query()->where('email', $invitation->email)->first()) {
            $invitation->team->users()->attach($user, ['role_id' =>  $invitation->role_id]);
            InertiaNotification::make()
                ->success()
                ->title('Invitation accepted')
                ->message(__('Great! You have accepted the invitation to join the :team team.', ['team' => $invitation->team->name]))
                ->send();

            // delete the invitation
            $invitation->delete();

            return to_route('dashboard');
        }

        if (auth()->check()) {
            InertiaNotification::make()
                ->error()
                ->title('Unauthorized Action')
                ->message('You are currently logged in as a different user. Please logout and try again.')
                ->send();

            return back();
        }

        return redirect()->temporarySignedRoute('teams.invitations.create.user', now()->addMinutes(30), [
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

        $user = resolve(CreateUser::class)->handle($request->validated());

        // Create a personal team for the user and it will the default team
        $personalTeam = resolve(CreateTeams::class)->handle(
            user: $user,
            attribute: [
                'name'    => $request->team,
                'user_id' => $user->id,
            ], markAsDefault: true);

        resolve(CreateRolePermission::class)->handle($personalTeam);

        event(new Registered($user));

        Auth::login($user);

        $request->session()->regenerate();

        resolve(CreateCurrentSessionTeam::class)->handle($personalTeam);

        // Accept the invitation
        $teamInvitation->acceptInvitation($user->email, $invitation->role_id);

        // delete the invitation
        $invitation->delete();

        InertiaNotification::make()
            ->success()
            ->title('Invitation accepted')
            ->message(__('Great! You have accepted the invitation to join the :team team.', ['team' => $teamInvitation->name]))
            ->send();

        return to_route('dashboard');

    }
}
