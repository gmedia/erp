<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission  The permission name to check
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $employee = $request->user()?->employee;

        if (!$employee || !$employee->hasPermission($permission)) {
            return response()->json([
                'message' => __('You do not have permission to perform this action.'),
            ], 403);
        }

        return $next($request);
    }
}
