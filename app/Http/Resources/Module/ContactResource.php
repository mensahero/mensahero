<?php

namespace App\Http\Resources\Module;

use App\Http\Resources\Api\Auth\SessionUserResource;
use App\Models\Contacts;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin Contacts */
class ContactResource extends JsonResource
{
    /**
     * @param Request $request
     *
     * @return array
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'mobile'       => $this->mobile,
            'country_code' => $this->country_code,
            'source'       => $this->source,
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,

            'user' => SessionUserResource::make($this->whenLoaded('user')),
        ];
    }
}
