<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdateProfileRequest;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    /**
     * Update the authenticated user's own name.
     *
     * Only the name is accepted. Email is read-only for self-service and is
     * changed by an administrator, so any email sent here is ignored and
     * email_verified_at is never touched. Password, roles, and other
     * attributes are never mass-assigned here.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->name = $request->validated('name');
        $user->save();

        return response()->json([
            'message' => __('profile.updated'),
            'user' => $user->fresh(),
        ]);
    }
}
