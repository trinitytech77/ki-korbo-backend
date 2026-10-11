<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Agent;
use App\Models\CustomerProfile;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_authenticated_user_can_fetch_profile(): void
    {
        $user = User::create([
            'name' => 'Meher Negar',
            'email' => 'meher@example.com',
            'phone' => '01845353922',
            'password' => bcrypt('password123'),
            'role' => UserRole::CUSTOMER->value,
            'status' => 'ACTIVE',
        ]);
        $user->assignRole(UserRole::CUSTOMER->value);

        CustomerProfile::create([
            'user_id' => $user->id,
            'phone' => '01845353922',
            'address' => 'Mirpur, Dhaka',
            'date_of_birth' => '1998-04-12',
            'gender' => 'female',
        ]);

        $token = $user->createToken('test_token')->plainTextToken;

        // Test GET /api/v1/me
        $responseMe = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/me');

        $responseMe->assertStatus(200)
            ->assertJsonPath('data.name', 'Meher Negar')
            ->assertJsonPath('data.email', 'meher@example.com')
            ->assertJsonPath('data.customer_profile.address', 'Mirpur, Dhaka')
            ->assertJsonPath('data.customer_profile.gender', 'female');

        // Test GET /api/v1/profile
        $responseProfile = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/profile');

        $responseProfile->assertStatus(200)
            ->assertJsonPath('data.name', 'Meher Negar');
    }

    public function test_user_can_update_profile_info(): void
    {
        $user = User::create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'phone' => '01711000000',
            'password' => bcrypt('password123'),
            'role' => UserRole::CUSTOMER->value,
            'status' => 'ACTIVE',
        ]);
        $user->assignRole(UserRole::CUSTOMER->value);

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/v1/profile', [
                'name' => 'Updated Name',
                'language' => 'bn',
                'address' => 'Gulshan-1, Dhaka',
                'date_of_birth' => '1995-10-10',
                'gender' => 'male',
                'emergency_contact' => '01999888777',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.language', 'bn')
            ->assertJsonPath('data.customer_profile.address', 'Gulshan-1, Dhaka')
            ->assertJsonPath('data.customer_profile.emergency_contact', '01999888777');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'language' => 'bn',
        ]);

        $this->assertDatabaseHas('customer_profiles', [
            'user_id' => $user->id,
            'address' => 'Gulshan-1, Dhaka',
            'gender' => 'male',
        ]);
    }

    public function test_user_can_update_password_with_current_password_verification(): void
    {
        $user = User::create([
            'name' => 'Password Test',
            'email' => 'pass@test.com',
            'password' => bcrypt('oldpassword123'),
            'role' => UserRole::CUSTOMER->value,
            'status' => 'ACTIVE',
        ]);

        $token = $user->createToken('test_token')->plainTextToken;

        // Fails with invalid current password
        $failResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/v1/profile', [
                'current_password' => 'wrongpassword',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $failResponse->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);

        // Succeeds with correct current password
        $successResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/v1/profile', [
                'current_password' => 'oldpassword123',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $successResponse->assertStatus(200);

        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }

    public function test_user_can_upload_avatar_image(): void
    {
        Storage::fake('public');

        $user = User::create([
            'name' => 'Avatar Test',
            'email' => 'avatar@test.com',
            'password' => bcrypt('password123'),
            'role' => UserRole::CUSTOMER->value,
            'status' => 'ACTIVE',
        ]);

        $token = $user->createToken('test_token')->plainTextToken;
        $file = UploadedFile::fake()->create('profile.jpg', 100, 'image/jpeg');

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/v1/profile', [
                'avatar' => $file,
            ]);

        $response->assertStatus(200);
        $this->assertNotNull($user->fresh()->avatar);
    }

    public function test_agent_can_update_agent_specific_profile_fields(): void
    {
        $agentUser = User::create([
            'name' => 'Agent Kabir',
            'email' => 'kabir@agent.com',
            'password' => bcrypt('password123'),
            'role' => UserRole::AGENT->value,
            'status' => 'ACTIVE',
        ]);
        $agentUser->assignRole(UserRole::AGENT->value);

        Agent::create([
            'user_id' => $agentUser->id,
            'status' => 'PENDING',
        ]);

        $token = $agentUser->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/v1/profile', [
                'bio' => 'Top rated agent with 5 years experience in passport offices.',
                'nid_number' => '19951234567890',
                'service_area' => 'Dhaka North',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.agent_profile.bio', 'Top rated agent with 5 years experience in passport offices.')
            ->assertJsonPath('data.agent_profile.nid_number', '19951234567890')
            ->assertJsonPath('data.agent_profile.service_area', 'Dhaka North');

        $this->assertDatabaseHas('agents', [
            'user_id' => $agentUser->id,
            'nid_number' => '19951234567890',
        ]);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/v1/me')->assertStatus(401);
        $this->getJson('/api/v1/profile')->assertStatus(401);
        $this->putJson('/api/v1/profile', ['name' => 'Hacker'])->assertStatus(401);
    }
}
