<?php

namespace App\Http\Controllers\Teams;

use Throwable;
use App\Actions\Teams\CreateCurrentSessionTeam;
use App\Actions\Teams\CreateRolePermission;
use App\Actions\Teams\CreateTeams;
use App\Actions\User\CreateUser;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Invitation;
use App\Services\InertiaNotification;
use Exception;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Jurager\Teams\Support\Facades\Teams as TeamsFacade;

class InviteController extends Controller
{
    /**
     * Accept the given invite.
     *
     * @param Request $request
     * @param string  $invitationId
     *
     * @throws Exception
     *
     * @return Application|\Illuminate\Foundation\Application|RedirectResponse|Redirector
     */
    public function inviteAccept(Request $request, string $invitationId): \Illuminate\Foundation\Application|Redirector|Application|RedirectResponse
    {
        // Get the invitation model
        /** @var Invitation $invitation */
        $invitation = TeamsFacade::instance('invitation')->whereKey($invitationId)->firstOrFail();

        // Get the team from invitation
        $team = $invitation->team;

        $user = $invitation->user;

        if (! $user) {
            return redirect()->signedRoute('teams.invitations.create.user', [
                'id' => $invitation->id,
            ]);
        }

        // Accept the invitation
        $team->inviteAccept($invitation->id);

        InertiaNotification::make()
            ->success()
            ->title('Invitation accepted')
            ->message(__('You have accepted the invitation to join the :team team.', ['team' => $team->name]))
            ->send();

        return to_route('dashboard');
    }

    public function createUser(Request $request, string $id): Response
    {
        // Get the invitation model
        /** @var Invitation $invitation */
        $invitation = TeamsFacade::instance('invitation')->whereKey($id)->firstOrFail();

        return Inertia::render('', [
            'email'        => $invitation->email,
            'invitationId' => $invitation->id,
        ]);

    }

    /**
     * @throws Throwable
     */
    public function store(RegisterRequest $request, string $id): RedirectResponse
    {
        // Get the invitation model
        /** @var Invitation $invitation */
        $invitation = TeamsFacade::instance('invitation')->whereKey($id)->firstOrFail();

        // Get the team from invitation
        $teamInvitation = $invitation->team;

        $user = app(CreateUser::class)->handle($request->validated());

        // Create a personal team for the user and it will the default team
        $personalTeam = app(CreateTeams::class)->handle(
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
}
