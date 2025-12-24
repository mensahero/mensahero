<?php

namespace App\Http\Resources;

use App\Http\Resources\Api\Auth\SessionUserResource;
use App\Models\Gateway;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin Gateway */
class GatewayResource extends JsonResource
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
            'id'              => $this->id,
            'device_provider' => $this->device_provider,
            'name'            => $this->name,
            'type'            => $this->type,
            'share'           => $this->share,
            'team_id'         => $this->team_id,
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,

            'user_id' => $this->user_id,

            'user' => new SessionUserResource($this->whenLoaded('user')),
        ];
    }
}
