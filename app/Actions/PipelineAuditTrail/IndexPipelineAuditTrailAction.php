<?php

namespace App\Actions\PipelineAuditTrail;

use App\Http\Requests\PipelineAuditTrail\IndexPipelineAuditTrailRequest;
use App\Models\PipelineStateLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class IndexPipelineAuditTrailAction
{
    public function execute(IndexPipelineAuditTrailRequest $request): LengthAwarePaginator|\Illuminate\Database\Eloquent\Collection
    {
        $query = PipelineStateLog::query()
            ->with([
                'pipelineEntityState.pipeline',
                'fromState',
                'toState',
                'transition',
                'performedBy'
            ]);

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

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

        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        if ($sortBy === 'performed_by') {
            $query->leftJoin('users', 'pipeline_state_logs.performed_by', '=', 'users.id')
                  ->orderBy('users.name', $sortDirection)
                  ->select('pipeline_state_logs.*');
        } else {
            $query->orderBy('pipeline_state_logs.' . $sortBy, $sortDirection);
        }

        if ($request->boolean('export')) {
             return $query->get();
        }

        $perPage = $request->get('per_page', 15);
        
        return $query->paginate($perPage)->withQueryString();
    }
}
