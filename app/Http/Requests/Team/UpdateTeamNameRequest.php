<?php

namespace App\Http\Requests\Team;

use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class UpdateTeamNameRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'    => ['required', 'string', 'max:255', Rule::unique(Team::class, 'name')->ignore($this->id)],
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
