<?php

namespace App\Http\Resources;

use App\Models\Post;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
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
            "name" => $this->name,
            "writer" => $this->writer,
            "publisher" => $this->publisher,
            "brief" => $this->brief,
            "start_page" => $this->start_page,
            "end_page" => $this->end_page,
            "link" => $this->link,
            "section" => new SectionResource($this->section),
            "type" => new BookTypeResource($this->type),
            "level" => $this->level,
            "language" => new LanguageResource($this->language),
            'media' => new MediaResource($this->media),
            'created_at' => $this->created_at,
            "posts" => PostResource::collection($this->whenLoaded('post')),
            'userBooks' => UserBookResource::collection($this->whenLoaded('userBooks')),
            'last_thesis' => $this->last_thesis ? new ThesisResource($this->last_thesis) : null,
        ];
    }
}