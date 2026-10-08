<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string'], // phone or email
            'otp' => ['required', 'string', 'size:6'],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'identifier.required' => 'Phone or email is required.',
            'otp.required' => 'Please enter the 6-digit OTP code.',
            'otp.size' => 'The OTP code must be exactly 6 digits.',
        ];
    }
}
