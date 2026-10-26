<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignAgentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'agent_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role', UserRole::AGENT->value),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'agent_id.required' => 'একজন এজেন্ট সিলেক্ট করুন।',
            'agent_id.exists'   => 'নির্বাচিত ইউজার একজন বৈধ এজেন্ট নয়।',
        ];
    }
}
