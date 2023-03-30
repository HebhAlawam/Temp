<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
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
            "id" => $this->id,
            "post_id" => $this->post_id,
            "user" => new UserInfoResource($this->user),
            "body" => $this->body,
            "type" => $this->type,
            "replies" => CommentResource::collection($this->replies),
            "media" => new MediaResource($this->media),
            "post" => new PostResource($this->whenLoaded('post')),
            "thesis" => new ThesisResource($this->whenLoaded('thesis')),
            "created_at" => $this->created_at,
        ];
    }
}