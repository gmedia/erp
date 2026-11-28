<?php

namespace App\Actions\AuditLog;

use App\Actions\Concerns\InteractsWithAuditTrailIndex;
use App\Http\Requests\AuditLogs\IndexAuditLogRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Activitylog\Models\Activity;

class IndexAuditLogAction
{
    use InteractsWithAuditTrailIndex;

    public function execute(IndexAuditLogRequest $request): LengthAwarePaginator|Collection
    {
        $query = Activity::query()->with('causer');

        $this->applyCreatedAtDateRange($request, $query);

        if ($request->filled('log_name')) {
            $query->where('log_name', $request->log_name);
        }

        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        if ($request->filled('subject_type')) {
            // Nama singkat (mis. "JournalEntry") dicocokkan sebagai substring;
            // FQCN penuh dicocokkan persis karena backslash adalah escape LIKE MySQL.
            $subjectType = $request->subject_type;

            if (str_contains($subjectType, '\\')) {
                $query->where('subject_type', $subjectType);
            } else {
                $query->where('subject_type', 'like', '%' . $subjectType);
            }
        }

        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->causer_id);
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';

            $query->where(function (Builder $q) use ($search) {
                $q->where('description', 'like', $search)
                    ->orWhere('subject_id', 'like', $search);
            });
        }

        $this->applyUserSortableOrdering(
            $request,
            $query,
            'activity_log',
            'causer_id',
            'activity_log.causer_id',
        );

        return $this->exportOrPaginate($request, $query);
    }
}
