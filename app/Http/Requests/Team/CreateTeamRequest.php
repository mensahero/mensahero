<?php

namespace App\Http\Requests\Team;

use Illuminate\Foundation\Http\FormRequest;
use Override;

class CreateTeamRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'    => ['required', 'string', 'max:255',  'unique:teams,name'],
            'default' => ['required', 'boolean:true,false'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'name.exists' => 'Try again a different team name.',
        ];
    }
}
