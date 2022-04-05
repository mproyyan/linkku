<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

class UserResource extends JsonResource
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
            'image_url' => $this->image ? URL::to('storage/' . $this->image) : null,
            'banner_url' => $this->banner ? URL::to('storage/' . $this->banner) : null,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
        ];
    }
}
