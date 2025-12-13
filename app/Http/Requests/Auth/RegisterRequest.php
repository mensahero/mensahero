<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\Rules\Password;
use Override;

class RegisterRequest extends FormRequest
{
    public function rules(): array
    {
        return App::isProduction() ? $this->productionRules() : $this->localRules();
    }

    public function productionRules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255', 'min:3'],
            'team'     => ['required', 'string', 'max:255', 'unique:teams,name'],
            'email'    => ['required', 'string', 'lowercase', 'disposable_email', 'email:rfc,dns,spoof,strict', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed',
                Password::min(8)
                    ->letters()
                    ->numbers()
                    ->mixedCase() // lower-case and upper-case letter
                    ->symbols()
                    ->uncompromised(),
            ],
            'password_confirmation' => ['required'],
        ];
    }

    public function localRules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255', 'min:3'],
            'team'     => ['required', 'string', 'max:255', 'unique:teams,name'],
            'email'    => ['required', 'string', 'lowercase',  'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed',
                Password::min(8),
            ],
            'password_confirmation' => ['required'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'team.exists' => 'Try again a different team name.',
        ];
    }
}
