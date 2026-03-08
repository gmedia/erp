<?php

namespace App\Http\Controllers;

use App\Actions\Users\SyncUserForEmployeeAction;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Http\Resources\Users\UserResource;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * Controller for user management operations.
 *
 * Handles user creation, updates, and linking users to employees.
 */
class UserController extends Controller
{
    /**
     * Get users for API dropdowns
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function apiIndex(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = User::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
        }

        // Return formatted for AsyncSelectField expect { id, name }
        $users = $query->limit(50)->get(['id', 'name']);
        
        return response()->json([
            'data' => $users
        ]);
    }

    /**
     * Get a single user for API dropdowns
     *
     * @param \App\Models\User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function apiShow(User $user): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
            ]
        ]);
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

        return response()->json([
            'user' => $user ? new UserResource($user) : null,
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
        $user = (new SyncUserForEmployeeAction())->execute($employee, $request->validated());

        return response()->json([
            'message' => 'User updated successfully.',
            'user' => new UserResource($user),
        ]);
    }
}
