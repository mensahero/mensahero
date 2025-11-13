<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Http\Request;
use Laravel\Fortify\Features;

class RoutePasswordProtectedMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword') && filled(auth()->user()->password)) {
            return app(RequirePassword::class)->handle($request, $next);
        }

        return $next($request);
    }
}
