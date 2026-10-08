<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\CustomerProfile;
use App\Models\User;
use App\Mail\SendOtpMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class AuthService
{
    public function __construct(
        protected ?SmsService $smsService = null
    ) {
        $this->smsService = $this->smsService ?? app(SmsService::class);
    }

    /**
     * Register a new user.
     *
     * @param array $data
     * @return array{user: User, token: string}
     */
    public function register(array $data): array
    {
        $roleName = $data['role'] ?? UserRole::CUSTOMER->value;

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => $roleName,
            'status' => AccountStatus::ACTIVE->value,
        ]);

        // Assign Spatie Role
        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        $user->assignRole($role);

        // Auto-create Customer Profile if role is customer
        if ($roleName === UserRole::CUSTOMER->value) {
            CustomerProfile::firstOrCreate(
                ['user_id' => $user->id],
                ['phone' => $user->phone]
            );
        }

        // Generate Sanctum plain text token
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Authenticate user via password or initiate OTP.
     *
     * @param array $data
     * @return array
     */
    public function login(array $data): array
    {
        $identifier = $data['login'] ?? $data['email'] ?? $data['phone'] ?? null;
        $isOtp = !empty($data['via_otp']);

        if (!$identifier) {
            throw ValidationException::withMessages([
                'login' => ['Email or phone number is required.'],
            ]);
        }

        // If login requested via OTP
        if ($isOtp) {
            $otp = $this->sendOtp($identifier);
            return [
                'requires_otp' => true,
                'identifier' => $identifier,
                'message' => 'OTP has been sent successfully.',
                'otp' => app()->isLocal() ? $otp : null,
            ];
        }

        // Password-based authentication
        $user = User::where('email', $identifier)
            ->orWhere('phone', $identifier)
            ->first();

        if (!$user || !Hash::check($data['password'] ?? '', $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Invalid email/phone or password.'],
            ]);
        }

        if ($user->status !== AccountStatus::ACTIVE->value) {
            throw ValidationException::withMessages([
                'login' => ['Your account is currently ' . strtolower($user->status) . '. Please contact support.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Generate and cache a 6-digit OTP for 5 minutes.
     *
     * @param string $identifier
     * @return string
     */
    public function sendOtp(string $identifier): string
    {
        $otp = sprintf('%06d', random_int(100000, 999999));
        $cacheKey = "otp:{$identifier}";

        // Store OTP in Cache/Redis for 5 minutes (300 seconds)
        Cache::put($cacheKey, $otp, now()->addMinutes(5));

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            // Send real email via configured mailer
            Mail::to($identifier)->send(new SendOtpMail($otp));
            Log::info("Sent OTP email to [{$identifier}]: {$otp}");
        } else {
            // Dispatch SMS
            $this->smsService->send($identifier, "Your KORBO verification code is {$otp}. Valid for 5 minutes.");
        }

        return $otp;
    }

    /**
     * Verify OTP and issue Sanctum token.
     *
     * @param string $identifier
     * @param string $otp
     * @return array{user: User, token: string}
     */
    public function verifyOtp(string $identifier, string $otp): array
    {
        $cacheKey = "otp:{$identifier}";
        $cachedOtp = Cache::get($cacheKey);

        if (!$cachedOtp || $cachedOtp !== $otp) {
            throw ValidationException::withMessages([
                'otp' => ['The OTP is invalid or has expired.'],
            ]);
        }

        // Clear OTP once consumed
        Cache::forget($cacheKey);

        // Find existing user by phone or email
        $user = User::where('phone', $identifier)
            ->orWhere('email', $identifier)
            ->first();

        // If user doesn't exist yet, auto-register them
        if (!$user) {
            $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);

            $user = User::create([
                'name' => 'User ' . substr($identifier, -4),
                'email' => $isEmail ? $identifier : null,
                'phone' => !$isEmail ? $identifier : null,
                'password' => Hash::make(uniqid('pass_', true)),
                'role' => UserRole::CUSTOMER->value,
                'status' => AccountStatus::ACTIVE->value,
                'phone_verified_at' => !$isEmail ? now() : null,
                'email_verified_at' => $isEmail ? now() : null,
            ]);

            $role = Role::firstOrCreate(['name' => UserRole::CUSTOMER->value, 'guard_name' => 'web']);
            $user->assignRole($role);

            CustomerProfile::firstOrCreate(
                ['user_id' => $user->id],
                ['phone' => $user->phone]
            );
        } else {
            // Mark verified
            if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                $user->email_verified_at = now();
            } else {
                $user->phone_verified_at = now();
            }
            $user->save();
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Invalidate current user token.
     *
     * @param User $user
     * @return bool
     */
    public function logout(User $user): bool
    {
        $currentToken = $user->currentAccessToken();

        if ($currentToken) {
            $currentToken->delete();
            return true;
        }

        return false;
    }
}
