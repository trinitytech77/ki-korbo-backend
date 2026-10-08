<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_email_and_phone(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Meher Hossain',
            'email' => 'meher@example.com',
            'phone' => '01711223344',
            'password' => 'secret123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'phone', 'role', 'status'],
                'token',
                'token_type',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'meher@example.com',
            'phone' => '01711223344',
            'role' => 'CUSTOMER',
        ]);

        $this->assertDatabaseHas('customer_profiles', [
            'phone' => '01711223344',
        ]);
    }

    public function test_user_can_login_with_password(): void
    {
        $user = User::create([
            'name' => 'Test Customer',
            'email' => 'customer@test.com',
            'password' => bcrypt('password123'),
            'role' => 'CUSTOMER',
            'status' => 'ACTIVE',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'customer@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['id', 'email'],
                'token',
            ]);
    }

    public function test_user_can_request_and_verify_otp(): void
    {
        // 1. Request OTP
        $response = $this->postJson('/api/v1/auth/login', [
            'phone' => '01812345678',
            'via_otp' => true,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'requires_otp' => true,
            ]);

        $otp = Cache::get('otp:01812345678');
        $this->assertNotNull($otp);

        // 2. Verify OTP
        $verifyResponse = $this->postJson('/api/v1/auth/otp/verify', [
            'identifier' => '01812345678',
            'otp' => $otp,
        ]);

        $verifyResponse->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['id', 'phone'],
                'token',
            ]);
    }

    public function test_user_can_request_and_verify_email_otp(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        // 1. Request OTP via email
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'founder@korbo.com',
            'via_otp' => true,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'requires_otp' => true,
            ]);

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\SendOtpMail::class, function ($mail) {
            return $mail->hasTo('founder@korbo.com');
        });

        $otp = Cache::get('otp:founder@korbo.com');
        $this->assertNotNull($otp);

        // 2. Verify OTP via email
        $verifyResponse = $this->postJson('/api/v1/auth/otp/verify', [
            'identifier' => 'founder@korbo.com',
            'otp' => $otp,
        ]);

        $verifyResponse->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['id', 'email'],
                'token',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'founder@korbo.com',
        ]);
    }

    public function test_authenticated_user_can_fetch_profile_and_logout(): void
    {
        $user = User::create([
            'name' => 'Meher Hossain',
            'email' => 'meher@korbo.com',
            'password' => bcrypt('secret123'),
            'role' => 'CUSTOMER',
            'status' => 'ACTIVE',
        ]);

        $token = $user->createToken('test_token')->plainTextToken;

        // Fetch /me
        $meResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/me');

        $meResponse->assertStatus(200)
            ->assertJsonPath('data.email', 'meher@korbo.com');

        // Logout
        $logoutResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout');

        $logoutResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        // Verify token deleted from database
        $this->assertDatabaseCount('personal_access_tokens', 0);

        // Reset auth guard in memory to test subsequent unauthorized request
        $this->app['auth']->forgetGuards();

        // Attempting to access /me with revoked token should fail
        $afterLogoutResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/me');

        $afterLogoutResponse->assertStatus(401);
    }
}
