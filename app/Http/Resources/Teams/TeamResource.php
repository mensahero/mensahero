<?php

namespace App\Http\Resources\Teams;

use App\Http\Resources\Auth\SessionUserResource;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin Team */
class TeamResource extends JsonResource
{
    public static $wrap = false;

    /**
     * @param Request $request
     *
     * @return array
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'default'    => $this->default,
            'user_owner' => SessionUserResource::make($this->whenLoaded('owner')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
