<?php

namespace App\Http\Controllers\Teams;

use App\Services\InertiaNotification;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Redirector;
use Jurager\Teams\Support\Facades\Teams as TeamsFacade;

class InviteController extends Controller
{
    /**
     * Accept the given invite.
     *
     * @param Request $request
     * @param         $invitationId
     *
     * @throws Exception
     *
     * @return Application|\Illuminate\Foundation\Application|RedirectResponse|Redirector
     */
    public function inviteAccept(Request $request, $invitationId): \Illuminate\Foundation\Application|Redirector|Application|RedirectResponse
    {
        // Get the invitation model
        $invitation = TeamsFacade::instance('invitation')->whereKey($invitationId)->firstOrFail();

        // Get the team from invitation
        $team = $invitation->team;

        // Accept the invitation
        $team->inviteAccept($invitation->id);

        InertiaNotification::make()
            ->success()
            ->title(__('Success! You have accepted the invitation to join the :team team.', ['team' => $team->name]))
            ->send();

        return to_route('dashboard');
    }
}
