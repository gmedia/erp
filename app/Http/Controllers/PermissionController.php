<?php

namespace App\Http\Controllers;

use App\Models\Permission;

/**
 * Controller for permission management operations.
 *
 * Handles display of permissions for employee permission assignment.
 */
class PermissionController extends Controller
{
    /**
     * Display a listing of all permissions.
     *
     * Returns all permissions for the permission management interface.
     * Ordered by name for better readability.
     */
    public function index(): \Illuminate\Http\JsonResponse
    {
        $permissions = Permission::query()
            ->orderBy('name')
            ->get();

        return response()->json($permissions);
    }
}
