<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display the users management page.
     */
    public function index(): Response
    {
        return Inertia::render('users/index');
    }

    /**
     * Get user data for an employee.
     */
    public function getUserByEmployee(Employee $employee): JsonResponse
    {
        $user = $employee->user;

        if (!$user) {
            return response()->json([
                'user' => null,
                'employee' => [
                    'name' => $employee->name,
                    'email' => $employee->email,
                ],
            ]);
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'employee' => [
                'name' => $employee->name,
                'email' => $employee->email,
            ],
        ]);
    }

    /**
     * Create or update user for an employee.
     */
    public function updateUser(Request $request, Employee $employee): JsonResponse
    {
        $existingUser = $employee->user;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($existingUser?->id),
            ],
            'password' => $existingUser ? 'nullable|string|min:8' : 'required|string|min:8',
        ]);

        if ($existingUser) {
            // Update existing user
            $userData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
            ];

            if (!empty($validated['password'])) {
                $userData['password'] = $validated['password'];
            }

            $existingUser->update($userData);
            $user = $existingUser;
        } else {
            // Create new user
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);

            // Link user to employee
            $employee->update(['user_id' => $user->id]);
        }

        return response()->json([
            'message' => 'User updated successfully.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }
}
