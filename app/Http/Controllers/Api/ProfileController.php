<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdateProfileRequest;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    /**
     * Update the authenticated user's own name and email.
     *
     * Only name and email are accepted. Password, roles, and other attributes
     * are never mass-assigned here. Social accounts are keyed by provider id,
     * so changing the email does not unlink them. A changed email must be
     * verified again.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $email = strtolower((string) $validated['email']);

        $emailChanged = $email !== $user->email;

        $user->name = $validated['name'];
        $user->email = $email;

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $user->fresh(),
        ]);
    }
}
