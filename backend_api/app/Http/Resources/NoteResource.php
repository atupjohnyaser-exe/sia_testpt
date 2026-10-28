<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $restricted = (int) $request->user()?->clearance_level < (int) $this->access_level;
        return ['id' => $this->id, 'subject_id' => $this->subject_id, 'body' => $restricted ? 'RESTRICTED' : $this->body, 'access_level' => $this->access_level, 'entered_by' => $this->user?->name, 'created_at' => $this->created_at];
    }
}
