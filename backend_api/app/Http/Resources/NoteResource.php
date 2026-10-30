<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $restricted = (int) $request->user()->clearance_level < $this->access_level;

        return [
            'id' => $this->id,
            'body' => $restricted ? 'RESTRICTED' : $this->body,
            'access_level' => $this->access_level,
            'restricted' => $restricted,
            'author' => $this->whenLoaded('author', fn () => $this->author?->only(['id', 'name', 'badge_id', 'initials'])),
            'created_at' => $this->created_at,
        ];
    }
}
