<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\RegisterAgentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\AgentRegisterRequest;
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
        protected AuthService         $authService,
        protected RegisterAgentAction $registerAgentAction,
    ) {}

    /**
     * Register a new user.
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
     * "Join as Agent" — registers user automatically with AGENT role.
     * Used from the home page "Join as Agent" module.
     *
     * POST /api/v1/auth/register/agent
     */
    public function registerAgent(AgentRegisterRequest $request): JsonResponse
    {
        $result = $this->registerAgentAction->execute($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'এজেন্ট হিসেবে নিবন্ধন সফল হয়েছে! আপনার আবেদন পর্যালোচনাধীন আছে।',
            'token'   => $result['token'],
            'user'    => [
                'id'     => $result['user']->id,
                'name'   => $result['user']->name,
                'email'  => $result['user']->email,
                'phone'  => $result['user']->phone,
                'role'   => $result['user']->role,
                'status' => $result['user']->status,
                'agent'  => [
                    'id'               => $result['user']->agent?->id,
                    'status'           => $result['user']->agent?->status, // PENDING
                    'nid_number'       => $result['user']->agent?->nid_number,
                    'experience_years' => $result['user']->agent?->experience_years,
                ],
            ],
        ], 201);
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
