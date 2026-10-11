<?php

namespace App\Actions;

use App\Models\CustomerProfile;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UpdateUserProfileAction
{
    /**
     * Update user and associated customer/agent profile details.
     *
     * @param User $user
     * @param array $data
     * @return User
     */
    public function execute(User $user, array $data): User
    {
        // 1. Password verification & update
        if (!empty($data['password'])) {
            if (!empty($data['current_password'])) {
                if (!Hash::check($data['current_password'], $user->password)) {
                    throw ValidationException::withMessages([
                        'current_password' => ['The current password provided is incorrect.'],
                    ]);
                }
            }

            $user->password = Hash::make($data['password']);
        }

        // 2. Avatar processing
        if (isset($data['avatar'])) {
            if ($data['avatar'] instanceof UploadedFile) {
                $path = $data['avatar']->store('avatars', 'public');
                $user->avatar = Storage::disk('public')->url($path);
            } elseif (is_string($data['avatar'])) {
                $user->avatar = $data['avatar'];
            } elseif (is_null($data['avatar'])) {
                $user->avatar = null;
            }
        }

        // 3. User basic fields
        if (array_key_exists('name', $data) && $data['name'] !== null) {
            $user->name = $data['name'];
        }
        if (array_key_exists('email', $data)) {
            $user->email = $data['email'];
        }
        if (array_key_exists('phone', $data)) {
            $user->phone = $data['phone'];
        }
        if (array_key_exists('language', $data) && $data['language'] !== null) {
            $user->language = $data['language'];
        }

        $user->save();

        // 4. Customer profile attributes
        $customerFields = ['address', 'date_of_birth', 'gender', 'emergency_contact'];
        $hasCustomerData = false;
        $customerData = [];

        foreach ($customerFields as $field) {
            if (array_key_exists($field, $data)) {
                $hasCustomerData = true;
                $customerData[$field] = $data[$field];
            }
        }

        if ($hasCustomerData || $user->isCustomer()) {
            if ($user->phone && !isset($customerData['phone'])) {
                $customerData['phone'] = $user->phone;
            }

            CustomerProfile::updateOrCreate(
                ['user_id' => $user->id],
                $customerData
            );
        }

        // 5. Agent profile attributes
        $agentFields = ['bio', 'nid_number', 'service_area'];
        $hasAgentData = false;
        $agentData = [];

        foreach ($agentFields as $field) {
            if (array_key_exists($field, $data)) {
                $hasAgentData = true;
                $agentData[$field] = $data[$field];
            }
        }

        if ($hasAgentData || $user->isAgent() || $user->isProfessional()) {
            Agent::updateOrCreate(
                ['user_id' => $user->id],
                $agentData
            );
        }

        return $user->fresh(['customerProfile', 'agent', 'roles', 'permissions']);
    }
}
