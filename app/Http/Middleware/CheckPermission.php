<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiErrorException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     * @param  string  $permission  The permission name to check
     */
    public function handle(Request $request, Closure $next, string $permission, bool $isApi = false): Response
    {
        $employee = $request->user()?->employee;

        if (! $employee || ! $employee->hasPermission($permission)) {
            if (! $isApi) {
                abort(403);
            }

            throw ApiErrorException::translated(
                403,
                'permission.denied',
                'You do not have permission to perform this action.',
            );
        }

        return $next($request);
    }
}
