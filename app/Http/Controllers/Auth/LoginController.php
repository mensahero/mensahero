<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\LoginUser;
use App\Actions\User\CreateUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\InertiaNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;

class LoginController extends Controller
{
    public function __construct(private readonly LoginUser $loginUser, private readonly CreateUser $createUser) {}

    /**
     * Show the application's login form.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/Login', [
            'canResetPassword'         => Route::has('password.request'),
            'canRegister'              => Route::has('register'),
            'canLoginViaGoogle'        => filled(config('services.google.client_id')) && filled(config('services.google.client_secret')),
            'canLoginViaZoho'          => filled(config('services.zoho.client_id')) && filled(config('services.zoho.client_secret')),
            'canLoginViaZoom'          => filled(config('services.zoom.client_id')) && filled(config('services.zoom.client_secret')),
            'notification'             => $request->session()->get('notification'),
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $user = $this->loginUser->handle($request);

        if (Features::enabled(Features::twoFactorAuthentication()) && $user->hasEnabledTwoFactorAuthentication()) {
            $request->session()->put([
                'login.id'       => $user->getKey(),
                'login.remember' => $request->boolean('remember'),
            ]);

            return to_route('two-factor.login');
        }

        Auth::login($user, $request->boolean('remember'));

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));

    }

    public function ssoCreate(Request $request, string $provider): RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        if (! in_array($provider, ['google', 'zoho', 'zoom'])) {
            InertiaNotification::make()
                ->error()
                ->title('Social sign on error')
                ->message('Social sign on provider not found.')
                ->send();

            return to_route('login');
        }

        return Inertia::location(Socialite::driver($provider)->redirect());
    }

    public function ssoStore(Request $request, string $provider): RedirectResponse
    {
        $ssoUser = Socialite::driver($provider)->user();

        $user = User::query()->where('email', $ssoUser->getEmail())->first();

        if (! $user) {
            // create new user
            $user = $this->createUser->handle([
                'name'              => $ssoUser->getName(),
                'email'             => $ssoUser->getEmail(),
                'email_verified_at' => Date::now(),
            ]);
        }

        Auth::login($user, $request->boolean('remember'));

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));

    }
}
