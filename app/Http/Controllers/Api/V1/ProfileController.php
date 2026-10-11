<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\UpdateUserProfileAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Get authenticated user profile.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load(['customerProfile', 'agent', 'roles', 'permissions']);

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Update authenticated user profile.
     *
     * @param UpdateProfileRequest $request
     * @param UpdateUserProfileAction $action
     * @return JsonResponse
     */
    public function update(UpdateProfileRequest $request, UpdateUserProfileAction $action): JsonResponse
    {
        $user = $action->execute($request->user(), $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => new UserResource($user),
        ]);
    }
}
