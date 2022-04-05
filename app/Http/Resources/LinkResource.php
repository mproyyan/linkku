<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LinkResource extends JsonResource
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
            'description' => $this->description,
            'excerpt' => $this->excerpt,
            'url' => $this->url,
            'views' => $this->views,
            'visibility' => $this->type->visibility
        ];
    }
}
