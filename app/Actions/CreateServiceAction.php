<?php

namespace App\Actions;

use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateServiceAction
{
    /**
     * Atomically create a new dynamic service with requirements, steps and fees.
     *
     * @param array $data
     * @return Service
     */
    public function execute(array $data): Service
    {
        return DB::transaction(function () use ($data) {
            // Generate unique slug if not provided
            $slug = !empty($data['slug']) 
                ? Str::slug($data['slug']) 
                : Str::slug($data['name']);

            $existingCount = Service::where('slug', $slug)->count();
            if ($existingCount > 0) {
                $slug = $slug . '-' . ($existingCount + 1);
            }

            // 1. Create Service
            $service = Service::create([
                'service_category_id' => $data['service_category_id'],
                'name' => $data['name'],
                'slug' => $slug,
                'description' => $data['description'] ?? null,
                'estimated_days' => $data['estimated_days'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            // 2. Attach Requirements
            if (!empty($data['requirements']) && is_array($data['requirements'])) {
                foreach ($data['requirements'] as $req) {
                    $service->requirements()->create([
                        'document_name' => $req['document_name'],
                        'is_mandatory' => $req['is_mandatory'] ?? true,
                    ]);
                }
            }

            // 3. Attach Steps
            if (!empty($data['steps']) && is_array($data['steps'])) {
                foreach ($data['steps'] as $index => $step) {
                    $service->steps()->create([
                        'step_name' => $step['step_name'],
                        'step_order' => $step['step_order'] ?? ($index + 1),
                    ]);
                }
            }

            // 4. Attach Fees
            if (!empty($data['fees']) && is_array($data['fees'])) {
                foreach ($data['fees'] as $fee) {
                    $service->fees()->create([
                        'fee_type' => strtoupper($fee['fee_type']),
                        'amount' => $fee['amount'] ?? 0.00,
                    ]);
                }
            }

            return $service->load(['category', 'requirements', 'steps', 'fees']);
        });
    }
}
