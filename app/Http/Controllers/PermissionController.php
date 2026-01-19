<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $permissions = Permission::query()
            ->orderBy('id') // Ensure deterministic order
            ->get();

        return Inertia::render('permissions/index', [
            'permissions' => $permissions,
        ]);
    }
}
