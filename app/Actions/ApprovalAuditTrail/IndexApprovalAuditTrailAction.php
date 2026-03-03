<?php

namespace App\Actions\ApprovalAuditTrail;

use App\Http\Requests\ApprovalAuditTrail\IndexApprovalAuditTrailRequest;
use App\Models\ApprovalAuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class IndexApprovalAuditTrailAction
{
    public function execute(IndexApprovalAuditTrailRequest $request): LengthAwarePaginator|\Illuminate\Database\Eloquent\Collection
    {
        $query = ApprovalAuditLog::query()
            ->with([
                'actor',
                'request',
            ]);

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        if ($request->filled('approvable_type')) {
            $query->where('approvable_type', 'like', '%' . $request->approvable_type);
        }

        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        if ($request->filled('actor_user_id')) {
            $query->where('actor_user_id', $request->actor_user_id);
        }

        if ($request->filled('search')) {
            $query->where(function (Builder $q) use ($request) {
                $q->where('approvable_id', 'like', '%' . $request->search . '%')
                  ->orWhereHas('actor', function (Builder $sq) use ($request) {
                      $sq->where('name', 'like', '%' . $request->search . '%');
                  });
            });
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        if ($sortBy === 'actor_user_id') {
            $query->leftJoin('users', 'approval_audit_logs.actor_user_id', '=', 'users.id')
                  ->orderBy('users.name', $sortDirection)
                  ->select('approval_audit_logs.*');
        } else {
            $query->orderBy('approval_audit_logs.' . $sortBy, $sortDirection);
        }

        if ($request->boolean('export')) {
             return $query->get();
        }

        $perPage = $request->get('per_page', 15);
        
        return $query->paginate($perPage)->withQueryString();
    }
}
