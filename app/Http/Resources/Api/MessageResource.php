<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'             => $this->id,
            'conversation_id'=> $this->conversation_id,
            'sender_id'      => $this->sender_id,
            'subject'        => $this->subject,
            'body'           => $this->body,
            'is_chat'        => (bool) $this->is_chat,
            'is_draft'       => (bool) $this->is_draft,
            'is_important'   => (bool) $this->is_important,
            'read'           => (bool) $this->read,
            'sender'         => new UserResource($this->whenLoaded('sender')),
            'created_at'     => $this->created_at,
        ];
    }
}

