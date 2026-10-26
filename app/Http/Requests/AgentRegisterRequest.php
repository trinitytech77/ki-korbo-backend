<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AgentRegisterRequest extends FormRequest
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
            'name'     => ['required', 'string', 'max:255'],
            'email'    => [
                'required_without:phone',
                'nullable',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'phone'    => [
                'required_without:email',
                'nullable',
                'string',
                'max:20',
                'unique:users,phone',
            ],
            'password' => ['required', 'string', 'min:6', 'confirmed'],

            // Agent-specific fields
            'nid_number'  => ['nullable', 'string', 'max:20'],
            'address'     => ['nullable', 'string', 'max:500'],
            'bio'         => ['nullable', 'string', 'max:1000'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'               => 'আপনার নাম দিন।',
            'email.required_without'      => 'ইমেইল অথবা ফোন নম্বর দিতে হবে।',
            'phone.required_without'      => 'ফোন নম্বর অথবা ইমেইল দিতে হবে।',
            'email.unique'                => 'এই ইমেইল দিয়ে ইতোমধ্যে একটি অ্যাকাউন্ট আছে।',
            'phone.unique'                => 'এই ফোন নম্বর দিয়ে ইতোমধ্যে একটি অ্যাকাউন্ট আছে।',
            'password.required'           => 'পাসওয়ার্ড দিন।',
            'password.min'                => 'পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের হতে হবে।',
            'password.confirmed'          => 'পাসওয়ার্ড কনফার্মেশন মিলছে না।',
        ];
    }
}
