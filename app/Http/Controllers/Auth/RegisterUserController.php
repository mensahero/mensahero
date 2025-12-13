<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Teams\CreateCurrentSessionTeam;
use App\Actions\Teams\CreateRolePermission;
use App\Actions\Teams\CreateTeams;
use App\Actions\User\CreateUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class RegisterUserController extends Controller
{
    public function __construct(private readonly CreateUser $createUser, private readonly CreateTeams $createTeams) {}

    public function create(): Response
    {
        return Inertia::render('auth/Register');
    }

    /**
     * @throws Throwable
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = $this->createUser->handle($request->validated());

        // Create a personal team for the user and it will the default team
        $teams = $this->createTeams->handle(
            user: $user,
            attribute: [
                'name'    => $request->team,
                'user_id' => $user->id,
            ], markAsDefault: true);

        resolve(CreateRolePermission::class)->handle($teams);

        event(new Registered($user));

        Auth::login($user);

        $request->session()->regenerate();

        resolve(CreateCurrentSessionTeam::class)->handle($teams);

        return to_route('dashboard');

    }
}
