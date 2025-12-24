<?php

namespace App\Http\Requests\Api\Modules\Devices;

use Illuminate\Foundation\Http\FormRequest;

class CreateDeviceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'device_name'  => ['required'],
            'manufacturer' => ['required'],
            'modelName'    => ['required'],
            'osName'       => ['required'],
            'extras'       => ['sometimes', 'array'],
        ];
    }
}
