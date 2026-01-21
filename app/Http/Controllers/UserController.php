<?php

namespace App\Http\Controllers;

use App\Http\Requests\Users\UpdateUserRequest;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller for user management operations.
 *
 * Handles user creation, updates, and linking users to employees.
 */
class UserController extends Controller
{
    /**
     * Display the users management page.
     *
     * @return \Inertia\Response
     */
    public function index(): Response
    {
        return Inertia::render('users/index');
    }

    /**
     * Get user data for an employee.
     *
     * Returns the user linked to the employee, or null if no user is linked.
     * Also includes basic employee information for display purposes.
     *
     * @param  \App\Models\Employee  $employee
     * @return \Illuminate\Http\JsonResponse
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
     *
     * If the employee already has a linked user, updates the existing user.
     * If no user is linked, creates a new user and links it to the employee.
     *
     * @param  \App\Http\Requests\Users\UpdateUserRequest  $request
     * @param  \App\Models\Employee  $employee
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUser(UpdateUserRequest $request, Employee $employee): JsonResponse
    {
        $existingUser = $employee->user;
        $validated = $request->validated();

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
