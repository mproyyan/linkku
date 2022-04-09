<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ArchiveResource extends JsonResource
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
            'author' => new UserResource($this->whenLoaded('author')),
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description ?? null,
            'excerpt' => $this->excerpt ?? null,
            'visibility' => $this->whenLoaded('type')->visibility,
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'links_count' => $this->links->count(),
        ];
    }
}
