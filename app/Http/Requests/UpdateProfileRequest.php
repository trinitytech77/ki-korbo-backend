<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            // User attributes
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($userId)],
            'avatar' => ['nullable'], // can be UploadedFile or URL string
            'language' => ['nullable', 'string', 'in:en,bn'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'password_confirmation' => ['required_with:password'],
            'current_password' => ['nullable', 'string'],

            // Customer Profile attributes
            'address' => ['nullable', 'string', 'max:500'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'emergency_contact' => ['nullable', 'string', 'max:20'],

            // Agent Profile attributes
            'bio' => ['nullable', 'string', 'max:1000'],
            'nid_number' => ['nullable', 'string', 'max:30'],
            'service_area' => ['nullable', 'string', 'max:255'],
        ];
    }
}
