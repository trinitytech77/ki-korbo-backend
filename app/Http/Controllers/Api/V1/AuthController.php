<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Http\Resources\AuthResource;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * Register a new user.
     *
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return response()->json(
            new AuthResource($result['user'], $result['token'], 'Registration successful.'),
            201
        );
    }

    /**
     * Authenticate user credentials or request OTP.
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        if (!empty($result['requires_otp'])) {
            return response()->json([
                'success' => true,
                'requires_otp' => true,
                'identifier' => $result['identifier'],
                'message' => $result['message'],
                'otp' => $result['otp'] ?? null,
            ]);
        }

        return response()->json(
            new AuthResource($result['user'], $result['token'], 'Login successful.')
        );
    }

    /**
     * Verify OTP code and issue token.
     *
     * @param VerifyOtpRequest $request
     * @return JsonResponse
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $result = $this->authService->verifyOtp(
            $request->validated('identifier'),
            $request->validated('otp')
        );

        return response()->json(
            new AuthResource($result['user'], $result['token'], 'OTP verified successfully.')
        );
    }

    /**
     * Invalidate current user token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out.',
        ]);
    }

    /**
     * Get authenticated user profile.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['customerProfile', 'agent']);

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
        ]);
    }
}
