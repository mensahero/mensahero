<?php

namespace App\Http\Resources\Api\Modules\Devices;

use App\Http\Resources\Api\Auth\SessionUserResource;
use App\Http\Resources\GatewayResource;
use App\Models\Devices;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin Devices */
class DevicesResource extends JsonResource
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
            'device_name'  => $this->device_name,
            'manufacturer' => $this->manufacturer,
            'modelName'    => $this->modelName,
            'osName'       => $this->osName,
            'status'       => $this->status,
            'last_seen'    => $this->last_seen,
            'extras'       => $this->extras,
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,

            'user_id' => $this->user_id,

            'gateways' => GatewayResource::collection($this->whenLoaded('gateways')),
            'user'     => new SessionUserResource($this->whenLoaded('user')),
        ];
    }
}
