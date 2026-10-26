<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AgentRegistrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_anyone_can_register_as_agent_via_join_as_agent_endpoint(): void
    {
        $response = $this->postJson('/api/v1/auth/register/agent', [
            'name'                  => 'Karim Mia',
            'email'                 => 'karim@agent.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'nid_number'            => '1234567890123',
            'address'               => 'Dhaka, Mirpur-10',
            'experience_years'      => 3,
            'bio'                   => 'আমি একজন অভিজ্ঞ ফিল্ড এজেন্ট।',
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('user.role', UserRole::AGENT->value)
                 ->assertJsonPath('user.agent.status', 'PENDING'); // admin approval pending

        $this->assertDatabaseHas('users', [
            'email' => 'karim@agent.com',
            'role'  => UserRole::AGENT->value,
        ]);

        $this->assertDatabaseHas('agents', [
            'nid_number'  => '1234567890123',
            'address'     => 'Dhaka, Mirpur-10',
            'status'      => 'PENDING',
        ]);
    }

    #[Test]
    public function test_agent_registration_with_phone_only(): void
    {
        $response = $this->postJson('/api/v1/auth/register/agent', [
            'name'                  => 'Rahim Agent',
            'phone'                 => '01800000001',
            'password'              => 'pass1234',
            'password_confirmation' => 'pass1234',
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('user.role', UserRole::AGENT->value);

        $this->assertDatabaseHas('users', [
            'phone' => '01800000001',
            'role'  => UserRole::AGENT->value,
        ]);
    }

    #[Test]
    public function test_agent_registration_requires_name_and_credentials(): void
    {
        $this->postJson('/api/v1/auth/register/agent', [])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['name', 'password']);
    }

    #[Test]
    public function test_agent_registration_password_confirmation_must_match(): void
    {
        $this->postJson('/api/v1/auth/register/agent', [
            'name'                  => 'Test Agent',
            'email'                 => 'agent@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'wrong_password',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['password']);
    }

    #[Test]
    public function test_duplicate_email_is_rejected_for_agent_registration(): void
    {
        // First registration
        $this->postJson('/api/v1/auth/register/agent', [
            'name'                  => 'First Agent',
            'email'                 => 'same@email.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(201);

        // Second registration with same email
        $this->postJson('/api/v1/auth/register/agent', [
            'name'                  => 'Second Agent',
            'email'                 => 'same@email.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function test_registered_agent_can_login(): void
    {
        // Register
        $this->postJson('/api/v1/auth/register/agent', [
            'name'                  => 'Login Test Agent',
            'email'                 => 'logintest@agent.com',
            'password'              => 'mypassword',
            'password_confirmation' => 'mypassword',
        ])->assertStatus(201);

        // Login
        $this->postJson('/api/v1/auth/login', [
            'login'    => 'logintest@agent.com',
            'password' => 'mypassword',
        ])->assertStatus(200)
          ->assertJsonPath('user.role', UserRole::AGENT->value);
    }
}
