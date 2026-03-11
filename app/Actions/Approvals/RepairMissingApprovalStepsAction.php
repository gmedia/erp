<?php

namespace App\Actions\Approvals;

use App\Models\ApprovalRequest;
use Illuminate\Support\Facades\DB;

class RepairMissingApprovalStepsAction
{
    /**
     * @return array{
     *     dry_run: bool,
     *     total: int,
     *     repaired: int,
     *     skipped: int,
     *     items: array<int, array{
     *         approval_request_id: int,
     *         approval_flow_id: int|null,
     *         approval_flow_code: string|null,
     *         status: string,
     *         current_step_order: int,
     *         flow_step_count: int,
     *         outcome: string,
     *     }>
     * }
     */
    public function execute(bool $dryRun = true): array
    {
        $requests = ApprovalRequest::with(['flow.steps'])
            ->doesntHave('steps')
            ->orderBy('id')
            ->get();

        $report = [
            'dry_run' => $dryRun,
            'total' => $requests->count(),
            'repaired' => 0,
            'skipped' => 0,
            'items' => [],
        ];

        foreach ($requests as $request) {
            $flow = $request->flow;
            $flowSteps = $flow?->steps ?? collect();
            $skipReason = $this->getSkipReason($request);

            if ($skipReason !== null) {
                $report['skipped']++;
                $report['items'][] = [
                    'approval_request_id' => $request->id,
                    'approval_flow_id' => $flow?->id,
                    'approval_flow_code' => $flow?->code,
                    'status' => $request->status,
                    'current_step_order' => $request->current_step_order,
                    'flow_step_count' => $flowSteps->count(),
                    'outcome' => $skipReason,
                ];

                continue;
            }

            if (! $dryRun) {
                DB::transaction(function () use ($request, $flowSteps) {
                    foreach ($flowSteps as $flowStep) {
                        $request->steps()->create([
                            'approval_flow_step_id' => $flowStep->id,
                            'step_order' => $flowStep->step_order,
                            'status' => 'pending',
                            'acted_by' => null,
                            'delegated_from' => null,
                            'action' => null,
                            'comments' => null,
                            'acted_at' => null,
                            'due_at' => null,
                        ]);
                    }
                });

                $report['repaired']++;
            }

            $report['items'][] = [
                'approval_request_id' => $request->id,
                'approval_flow_id' => $flow?->id,
                'approval_flow_code' => $flow?->code,
                'status' => $request->status,
                'current_step_order' => $request->current_step_order,
                'flow_step_count' => $flowSteps->count(),
                'outcome' => $dryRun ? 'repairable' : 'repaired',
            ];
        }

        return $report;
    }

    private function getSkipReason(ApprovalRequest $request): ?string
    {
        if (! in_array($request->status, ['pending', 'in_progress'], true)) {
            return 'manual_review_non_active_request';
        }

        if ($request->current_step_order !== 1) {
            return 'manual_review_current_step_gt_1';
        }

        $flow = $request->flow;

        if (! $flow) {
            return 'missing_approval_flow';
        }

        if ($flow->steps->isEmpty()) {
            return 'approval_flow_has_no_steps';
        }

        if (! $flow->steps->contains('step_order', $request->current_step_order)) {
            return 'current_step_missing_in_flow';
        }

        return null;
    }
}