<?php

namespace App\Actions\ApprovalAuditTrail;

use App\Actions\Concerns\InteractsWithAuditTrailIndex;
use App\Http\Requests\ApprovalAuditTrail\IndexApprovalAuditTrailRequest;
use App\Models\ApprovalAuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class IndexApprovalAuditTrailAction
{
    use InteractsWithAuditTrailIndex;

    public function execute(
        IndexApprovalAuditTrailRequest $request
    ): LengthAwarePaginator|Collection {
        $query = ApprovalAuditLog::query()
            ->with([
                'actor',
                'request',
            ]);

        $this->applyCreatedAtDateRange($request, $query);

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

        $this->applyUserSortableOrdering(
            $request,
            $query,
            'approval_audit_logs',
            'actor_user_id',
            'approval_audit_logs.actor_user_id',
        );

        return $this->exportOrPaginate($request, $query);
    }
}
