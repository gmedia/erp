<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckEntityStatePermission
{
    /**
     * Entity types whose pipeline endpoints are also reachable by users who hold
     * the document module permission (e.g. purchase_order) instead of `pipeline`.
     * Per-transition `required_permission` is still enforced by EntityStateController.
     *
     * @var array<string, string>
     */
    private const DOCUMENT_MODULE_PERMISSIONS = [
        'purchase_order' => 'purchase_order',
        'purchase_request' => 'purchase_request',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $employee = $request->user()?->employee;
        $entityType = (string) $request->route('entityType');

        $allowed = $employee !== null
            && (
                $employee->hasPermission('pipeline')
                || (
                    isset(self::DOCUMENT_MODULE_PERMISSIONS[$entityType])
                    && $employee->hasPermission(self::DOCUMENT_MODULE_PERMISSIONS[$entityType])
                )
            );

        if (! $allowed) {
            return response()->json([
                'message' => __('You do not have permission to perform this action.'),
            ], 403);
        }

        return $next($request);
    }
}
