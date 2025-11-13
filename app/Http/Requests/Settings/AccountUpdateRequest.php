<?php

namespace App\Http\Requests\Settings;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\Rule;

class AccountUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return App::isProduction() ? $this->productionRules() : $this->localRules();
    }

    public function productionRules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255', 'min:3'],
            'email'    => ['required', 'string', 'disposable_email', 'email:rfc,dns,spoof,strict', 'max:255', 'lowercase',  Rule::unique(User::class)
                ->ignore($this->user()->id), ],
        ];
    }

    public function localRules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255', 'min:3'],
            'email'    => ['required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)
                    ->ignore($this->user()->id), ],
        ];
    }
}
