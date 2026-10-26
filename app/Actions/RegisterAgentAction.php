<?php

namespace App\Actions;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RegisterAgentAction
{
    /**
     * Register a new user with AGENT role automatically.
     * Called from the "Join as Agent" endpoint.
     *
     * @param  array $data  Validated request data
     * @return array{user: User, token: string}
     */
    public function execute(array $data): array
    {
        // 1. Create the user — role is always AGENT here
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'] ?? null,
            'phone'    => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'role'     => UserRole::AGENT->value,
            'status'   => AccountStatus::ACTIVE->value,
        ]);

        // 2. Assign Spatie AGENT role
        $role = Role::firstOrCreate([
            'name'       => UserRole::AGENT->value,
            'guard_name' => 'web',
        ]);
        $user->assignRole($role);

        // 3. Auto-create Agent profile (status = PENDING — admin approve করবে)
        Agent::firstOrCreate(
            ['user_id' => $user->id],
            [
                'nid_number'       => $data['nid_number'] ?? null,
                'address'          => $data['address'] ?? null,
                'bio'              => $data['bio'] ?? null,
                'experience_years' => (int) ($data['experience_years'] ?? 0),
                'status'           => 'PENDING',
            ]
        );

        // 4. Generate Sanctum token
        $token = $user->createToken('agent_auth_token')->plainTextToken;

        return [
            'user'  => $user->load('agent'),
            'token' => $token,
        ];
    }
}
