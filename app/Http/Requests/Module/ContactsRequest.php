<?php

namespace App\Http\Requests\Module;

use App\Concerns\ContactSources;
use App\Concerns\MobileCountryCode;
use App\Models\Contacts;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class ContactsRequest extends FormRequest
{
    public function rules(): array
    {
        return $this->isMethod('POST') ? $this->createRules() : $this->updateRules();
    }

    public function createRules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:255'],
            'mobile'       => ['required', 'string', 'max:255', 'phone:country_code', 'unique:contacts,mobile'],
            'country_code' => ['required', 'required_with:mobile', Rule::enum(MobileCountryCode::class)],
            'source'       => ['required', Rule::enum(ContactSources::class)],
        ];
    }

    public function updateRules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:255'],
            'mobile'       => ['required', 'string', 'max:255', 'phone:country_code', Rule::unique(Contacts::class)->ignore($this->route('id'))],
            'country_code' => ['required', 'required_with:mobile', Rule::enum(MobileCountryCode::class)],
            'source'       => ['required', Rule::enum(ContactSources::class)],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'phone' => 'The :attribute field must be a valid number.',
        ];
    }
}
