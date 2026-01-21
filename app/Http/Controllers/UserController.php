<?php

namespace App\Http\Controllers;

use App\Actions\Users\SyncUserForEmployeeAction;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Http\Resources\Users\UserResource;
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
        $user = (new SyncUserForEmployeeAction())->execute($employee, $request->validated());

        return response()->json([
            'message' => 'User updated successfully.',
            'user' => new UserResource($user),
        ]);
    }
}
