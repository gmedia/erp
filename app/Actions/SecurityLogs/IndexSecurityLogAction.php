<?php

namespace App\Actions\SecurityLogs;

use App\Actions\Concerns\InteractsWithAuditTrailIndex;
use App\Http\Requests\SecurityLogs\IndexSecurityLogRequest;
use App\Models\SecurityLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class IndexSecurityLogAction
{
    use InteractsWithAuditTrailIndex;

    public function execute(IndexSecurityLogRequest $request): LengthAwarePaginator|Collection
    {
        $query = SecurityLog::query()->with('user');

        $this->applyCreatedAtDateRange($request, $query);

        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('ip_address')) {
            $query->where('ip_address', 'like', $request->ip_address . '%');
        }

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';

            $query->where(function (Builder $q) use ($search) {
                $q->where('attempted_email', 'like', $search)
                    ->orWhere('ip_address', 'like', $search)
                    ->orWhereHas('user', function (Builder $sq) use ($search) {
                        $sq->where('name', 'like', $search)
                            ->orWhere('email', 'like', $search);
                    });
            });
        }

        $this->applyUserSortableOrdering(
            $request,
            $query,
            'security_logs',
            'user_id',
            'security_logs.user_id',
        );

        return $this->exportOrPaginate($request, $query);
    }
}
