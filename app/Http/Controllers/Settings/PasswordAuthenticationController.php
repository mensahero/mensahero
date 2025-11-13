<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Middleware\RoutePasswordProtectedMiddleware;
use App\Http\Requests\Settings\UpdatePasswordRequest;
use App\Services\InertiaNotification;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class PasswordAuthenticationController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [new Middleware(RoutePasswordProtectedMiddleware::class, only: ['edit'])];
    }

    /**
     * Show the user's password and authentication settings page.
     */
    public function edit(): Response
    {
        return Inertia::render('settings/PasswordAuthentication');
    }

    /**
     * Update the user's password.
     *
     * @throws Exception
     */
    public function update(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        InertiaNotification::make()
            ->success()
            ->title('Password updated')
            ->message('Your password has been updated')
            ->send();

        return back();
    }
}
