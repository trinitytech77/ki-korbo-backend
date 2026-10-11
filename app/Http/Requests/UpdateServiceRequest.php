<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isStaff();
    }

    public function rules(): array
    {
        $serviceId = $this->route('service') instanceof \App\Models\Service 
            ? $this->route('service')->id 
            : $this->route('service');

        return [
            'service_category_id' => ['sometimes', 'required', 'exists:service_categories,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('services', 'slug')->ignore($serviceId)],
            'description' => ['nullable', 'string'],
            'estimated_days' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],

            // Requirements array
            'requirements' => ['nullable', 'array'],
            'requirements.*.id' => ['nullable', 'integer'],
            'requirements.*.document_name' => ['required_with:requirements', 'string', 'max:255'],
            'requirements.*.is_mandatory' => ['nullable', 'boolean'],

            // Steps array
            'steps' => ['nullable', 'array'],
            'steps.*.id' => ['nullable', 'integer'],
            'steps.*.step_name' => ['required_with:steps', 'string', 'max:255'],
            'steps.*.step_order' => ['nullable', 'integer'],

            // Fees array
            'fees' => ['nullable', 'array'],
            'fees.*.id' => ['nullable', 'integer'],
            'fees.*.fee_type' => ['required_with:fees', 'string', 'in:GOVERNMENT,KORBO,AGENT'],
            'fees.*.amount' => ['required_with:fees', 'numeric', 'min:0'],
        ];
    }
}
