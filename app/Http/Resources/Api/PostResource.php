<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'       => $this->id,
            'title'    => $this->title,
            'content'  => $this->content,
            'status'   => $this->status,
            'created_at'=> $this->created_at,

            'files' => FileResource::collection($this->whenLoaded('files')),
        ];
    }
}
