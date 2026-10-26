<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskStatusHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'status'     => $this->status,
            'notes'      => $this->notes,
            'changed_by' => $this->whenLoaded('changedBy', function () {
                return [
                    'id'   => $this->changedBy->id,
                    'name' => $this->changedBy->name,
                    'role' => $this->changedBy->role,
                ];
            }),
            'changed_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
