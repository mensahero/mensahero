<?php

namespace App\Http\Resources;

use App\Http\Resources\Api\Auth\SessionUserResource;
use App\Models\Providers;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Providers */
class ProvidersResource extends JsonResource
{
    /**
     * @param Request $request
     *
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'connection_type' => $this->connection_type,
            'scope'           => $this->scope,
            'client_id'       => $this->client_id,
            'client_secret'   => $this->client_secret,
            'refresh_token'   => $this->refresh_token,
            'access_token'    => $this->access_token,
            'domain'          => $this->domain,
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,

            'user_id' => $this->user_id,

            'user' => new SessionUserResource($this->whenLoaded('user')),
        ];
    }
}
