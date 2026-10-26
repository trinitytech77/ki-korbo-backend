<?php

namespace App\Http\Requests;

use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskStatusRequest extends FormRequest
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
            'status' => ['required', 'string', Rule::in(TaskStatus::values())],
            'notes'  => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'একটি স্ট্যাটাস সিলেক্ট করুন।',
            'status.in'       => 'অবৈধ স্ট্যাটাস। গ্রহণযোগ্য: ' . implode(', ', TaskStatus::values()),
        ];
    }
}
