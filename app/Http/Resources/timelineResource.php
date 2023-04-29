<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TimelineResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'type' => new TimelineTypeResource($this->whenLoaded('type')),
            'user' => new UserInfoResource($this->whenLoaded('profile') ? $this->profile->user : null),
            'group' => $this->whenLoaded('group')
        ];
    }
}