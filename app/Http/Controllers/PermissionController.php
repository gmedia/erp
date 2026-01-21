<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Inertia\Inertia;
use Inertia\Response;

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
     * Returns all permissions ordered by ID for permission management interface.
     *
     * @return \Inertia\Response
     */
    public function index(): Response
    {
        $permissions = Permission::query()
            ->orderBy('id')
            ->get();

        return Inertia::render('permissions/index', [
            'permissions' => $permissions,
        ]);
    }
}
