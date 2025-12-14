<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'slug'       => $this->slug,
            'is_active'  => (bool) $this->is_active,
            'country'    => $this->country ?? null,
            'parent_id'  => $this->parent_id,
            'icon'       => $this->icon,
            'news_count' => isset($this->news_count) ? (int) $this->news_count : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

