<?php

namespace App\Http\Resources;

use App\Http\Resources\Api\Auth\SessionUserResource;
use App\Models\Devices;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Devices */
class DevicesResource extends JsonResource
{
    /**
     * @param Request $request
     *
     * @return array
     */
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

            'user' => new SessionUserResource($this->whenLoaded('user')),
        ];
    }
}
