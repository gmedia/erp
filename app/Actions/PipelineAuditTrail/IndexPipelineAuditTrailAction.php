<?php

namespace App\Actions\PipelineAuditTrail;

use App\Actions\Concerns\InteractsWithAuditTrailIndex;
use App\Http\Requests\PipelineAuditTrail\IndexPipelineAuditTrailRequest;
use App\Models\PipelineStateLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class IndexPipelineAuditTrailAction
{
    use InteractsWithAuditTrailIndex;

    public function execute(
        IndexPipelineAuditTrailRequest $request
    ): LengthAwarePaginator|Collection {
        $query = PipelineStateLog::query()
            ->with([
                'pipelineEntityState.pipeline',
                'fromState',
                'toState',
                'transition',
                'performedBy',
            ]);

        $this->applyCreatedAtDateRange($request, $query);

        if ($request->filled('entity_type')) {
            $query->where('entity_type', 'like', '%' . $request->entity_type);
        }

        if ($request->filled('pipeline_id')) {
            $query->whereHas('pipelineEntityState', function (Builder $q) use ($request) {
                $q->where('pipeline_id', $request->pipeline_id);
            });
        }

        if ($request->filled('from_state_id')) {
            $query->where('from_state_id', $request->from_state_id);
        }

        if ($request->filled('to_state_id')) {
            $query->where('to_state_id', $request->to_state_id);
        }

        if ($request->filled('performed_by')) {
            $query->where('performed_by', $request->performed_by);
        }

        if ($request->filled('search')) {
            $query->where(function (Builder $q) use ($request) {
                $q->where('entity_id', 'like', '%' . $request->search . '%')
                    ->orWhere('comment', 'like', '%' . $request->search . '%')
                    ->orWhereHas('performedBy', function (Builder $sq) use ($request) {
                        $sq->where('name', 'like', '%' . $request->search . '%');
                    })
                    ->orWhereHas('transition', function (Builder $sq) use ($request) {
                        $sq->where('name', 'like', '%' . $request->search . '%');
                    });
            });
        }

        $this->applyUserSortableOrdering(
            $request,
            $query,
            'pipeline_state_logs',
            'performed_by',
            'pipeline_state_logs.performed_by',
        );

        return $this->exportOrPaginate($request, $query);
    }
}
