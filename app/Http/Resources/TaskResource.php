<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'     => $this->id,
            'status' => $this->status,

            // Service info
            'service' => $this->whenLoaded('service', function () {
                return [
                    'id'             => $this->service->id,
                    'name'           => $this->service->name,
                    'slug'           => $this->service->slug,
                    'estimated_days' => $this->service->estimated_days,
                ];
            }),

            // Customer info
            'customer' => $this->whenLoaded('customer', function () {
                return [
                    'id'    => $this->customer->id,
                    'name'  => $this->customer->name,
                    'email' => $this->customer->email,
                    'phone' => $this->customer->phone,
                ];
            }),

            // Assigned agent info (nullable)
            'agent' => $this->whenLoaded('agent', function () {
                return $this->agent ? [
                    'id'    => $this->agent->id,
                    'name'  => $this->agent->name,
                    'email' => $this->agent->email,
                    'phone' => $this->agent->phone,
                ] : null;
            }),

            // Fee breakdown
            'fees' => [
                'government_fee' => (float) $this->government_fee,
                'service_fee'    => (float) $this->service_fee,
                'agent_fee'      => (float) $this->agent_fee,
                'total_amount'   => (float) $this->total_amount,
            ],

            // Task details
            'location'         => $this->location,
            'appointment_date' => $this->appointment_date?->toDateString(),
            'notes'            => $this->notes,
            'completed_at'     => $this->completed_at?->toIso8601String(),

            // Documents count
            'documents_count' => $this->when(
                $this->relationLoaded('documents'),
                fn() => $this->documents->count()
            ),

            // Status history
            'status_history' => TaskStatusHistoryResource::collection(
                $this->whenLoaded('statusHistories')
            ),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
