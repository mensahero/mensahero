<?php

namespace App\Http\Controllers\Teams;

use App\Actions\Teams\RetrieveCurrentSessionTeam;
use App\Http\Controllers\Controller;
use App\Http\Resources\Teams\TeamResource;
use App\Http\Resources\Teams\TeamsMenuResource;
use App\Models\Team;
use App\Services\InertiaNotification;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class TeamsController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $team = app(RetrieveCurrentSessionTeam::class)->handle();
        $team->load(['owner']);

        return Inertia::render('Teams', [
            'team' => TeamResource::make($team),
        ]);
    }

    public function updateTeamName(Request $request, string $id)
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

    public function getTeams(): JsonResponse
    {
        $user = auth()->user();

        $teams = $user->allTeams();

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
