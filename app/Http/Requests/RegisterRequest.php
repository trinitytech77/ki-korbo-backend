<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required_without:phone',
                'nullable',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'phone' => [
                'required_without:email',
                'nullable',
                'string',
                'max:20',
                'unique:users,phone',
            ],
            'password' => ['required', 'string', 'min:4'],
            'role' => ['sometimes', 'string', Rule::in(UserRole::values())],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'email.required_without' => 'Please provide either an email address or a phone number.',
            'phone.required_without' => 'Please provide either a phone number or an email address.',
        ];
    }
}
