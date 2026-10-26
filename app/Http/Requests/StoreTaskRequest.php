<?php

namespace App\Http\Requests;

use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
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
            'service_id'       => ['required', 'integer', 'exists:services,id'],
            'location'         => ['required', 'string', 'max:500'],
            'appointment_date' => ['nullable', 'date', 'after:today'],
            'notes'            => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'service_id.required' => 'একটি সার্ভিস সিলেক্ট করুন।',
            'service_id.exists'   => 'নির্বাচিত সার্ভিসটি পাওয়া যায়নি।',
            'location.required'   => 'আপনার লোকেশন দিন।',
            'appointment_date.after' => 'অ্যাপয়েন্টমেন্টের তারিখ আজকের পরের হতে হবে।',
        ];
    }
}
