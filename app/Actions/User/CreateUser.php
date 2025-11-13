<?php

namespace App\Actions\User;

use App\Mail\Users\WelcomeMail;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class CreateUser
{
    /**
     * @throws Throwable
     */
    public function handle(array $attributes): User
    {
        return DB::transaction(function () use ($attributes) {
            $user = User::query()->create([
                ...$attributes,
                'name'     => Str::ucwords($attributes['name']),
                'password' => Arr::has($attributes, 'password') ? Hash::make($attributes['password']) : null,
            ]);

            if ($user instanceof MustVerifyEmail) {
                $user->sendEmailVerificationNotification();
            }

            // you can add more logic here, like adding roles, etc.

            Mail::to($user)->queue(new WelcomeMail($user));

            return $user;
        });
    }
}
