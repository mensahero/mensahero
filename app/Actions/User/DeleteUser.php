<?php

namespace App\Actions\User;

use App\Mail\Users\UserDeletedMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class DeleteUser
{
    public function handle(Request $request): void
    {
        $user = $request->user();

        Auth::logout();

        Mail::to($user)->queue(new UserDeletedMail($user));

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
