<?php

namespace App\Actions;

use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UpdateServiceAction
{
    /**
     * Atomically update a service and sync requirements, steps and fees.
     *
     * @param Service $service
     * @param array $data
     * @return Service
     */
    public function execute(Service $service, array $data): Service
    {
        return DB::transaction(function () use ($service, $data) {
            // Update slug if explicitly passed
            if (!empty($data['slug'])) {
                $service->slug = Str::slug($data['slug']);
            }

            // Update standard attributes
            $attributes = ['service_category_id', 'name', 'description', 'estimated_days', 'is_active'];
            foreach ($attributes as $attr) {
                if (array_key_exists($attr, $data)) {
                    $service->{$attr} = $data[$attr];
                }
            }

            $service->save();

            // Synchronize Requirements if supplied
            if (array_key_exists('requirements', $data) && is_array($data['requirements'])) {
                $service->requirements()->delete();
                foreach ($data['requirements'] as $req) {
                    $service->requirements()->create([
                        'document_name' => $req['document_name'],
                        'is_mandatory' => $req['is_mandatory'] ?? true,
                    ]);
                }
            }

            // Synchronize Steps if supplied
            if (array_key_exists('steps', $data) && is_array($data['steps'])) {
                $service->steps()->delete();
                foreach ($data['steps'] as $index => $step) {
                    $service->steps()->create([
                        'step_name' => $step['step_name'],
                        'step_order' => $step['step_order'] ?? ($index + 1),
                    ]);
                }
            }

            // Synchronize Fees if supplied
            if (array_key_exists('fees', $data) && is_array($data['fees'])) {
                $service->fees()->delete();
                foreach ($data['fees'] as $fee) {
                    $service->fees()->create([
                        'fee_type' => strtoupper($fee['fee_type']),
                        'amount' => $fee['amount'] ?? 0.00,
                    ]);
                }
            }

            return $service->fresh(['category', 'requirements', 'steps', 'fees']);
        });
    }
}
