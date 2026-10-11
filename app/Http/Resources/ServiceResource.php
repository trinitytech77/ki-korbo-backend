<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service_category_id' => $this->service_category_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'estimated_days' => $this->estimated_days,
            'is_active' => (bool) $this->is_active,
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                    'icon' => $this->category->icon,
                ];
            }),
            'requirements' => ServiceRequirementResource::collection($this->whenLoaded('requirements')),
            'steps' => ServiceStepResource::collection($this->whenLoaded('steps')),
            'fees' => ServiceFeeResource::collection($this->whenLoaded('fees')),
            'fee_breakdown' => $this->fee_breakdown,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
