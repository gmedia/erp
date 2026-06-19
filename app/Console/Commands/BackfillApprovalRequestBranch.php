<?php

namespace App\Console\Commands;

use App\Domain\Branch\BranchResolverRegistry;
use App\Models\ApprovalRequest;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

class BackfillApprovalRequestBranch extends Command
{
    /**
     * @var string
     */
    protected $signature = 'approval-requests:backfill-branch {--dry-run : Report what would change without writing} {--chunk=500 : Number of rows processed per batch}';

    /**
     * @var string
     */
    protected $description = 'Backfill approval_requests.branch_id from each request polymorphic approvable. Idempotent: only touches rows where branch_id is null.';

    public function handle(BranchResolverRegistry $registry): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunkSize = max(1, (int) $this->option('chunk'));

        if ($dryRun) {
            $this->warn('DRY RUN — no rows will be written.');
        }

        $branchBearingTypes = $registry->branchBearingTypes();

        /** @var array<string, array{matched: int, resolved: int, unresolved: int}> $stats */
        $stats = [];
        $totalUpdated = 0;

        foreach ($branchBearingTypes as $approvableType) {
            $relation = array_map(
                static fn (string $relation): string => "approvable.{$relation}",
                $registry->relationsFor($approvableType),
            );

            $stats[$approvableType] = ['matched' => 0, 'resolved' => 0, 'unresolved' => 0];

            ApprovalRequest::query()
                ->whereNull('branch_id')
                ->where('approvable_type', $approvableType)
                ->with(array_merge(['approvable'], $relation))
                ->chunkById($chunkSize, function ($requests) use ($registry, &$stats, &$totalUpdated, $approvableType, $dryRun): void {
                    foreach ($requests as $request) {
                        $stats[$approvableType]['matched']++;

                        $approvable = $request->approvable;

                        if (! $approvable instanceof Model) {
                            $stats[$approvableType]['unresolved']++;

                            continue;
                        }

                        $branchId = $registry->resolve($approvable);

                        if ($branchId === null) {
                            $stats[$approvableType]['unresolved']++;

                            continue;
                        }

                        $stats[$approvableType]['resolved']++;

                        if (! $dryRun) {
                            $request->branch_id = $branchId;
                            $request->save();
                        }

                        $totalUpdated++;
                    }
                });
        }

        $this->renderSummary($stats, $dryRun);

        $nullSkipped = ApprovalRequest::query()
            ->whereNull('branch_id')
            ->whereNotIn('approvable_type', $branchBearingTypes)
            ->count();

        $this->line('');
        $this->info(sprintf(
            '%s %d approval requests from branch-bearing approvables.',
            $dryRun ? 'Would update' : 'Updated',
            $totalUpdated,
        ));
        $this->line(sprintf(
            '%d requests left null by design (no-branch approvables or unregistered approvable_type).',
            $nullSkipped,
        ));

        return self::SUCCESS;
    }

    /**
     * @param  array<string, array{matched: int, resolved: int, unresolved: int}>  $stats
     */
    private function renderSummary(array $stats, bool $dryRun): void
    {
        $rows = [];

        foreach ($stats as $approvableType => $counts) {
            if ($counts['matched'] === 0) {
                continue;
            }

            $rows[] = [
                class_basename($approvableType),
                $counts['matched'],
                $counts['resolved'],
                $counts['unresolved'],
            ];
        }

        if ($rows === []) {
            $this->line('No null-branch approval requests from branch-bearing approvables found.');

            return;
        }

        $this->table(
            ['Approvable', 'Matched', $dryRun ? 'Would set' : 'Set', 'Left null'],
            $rows,
        );
    }
}
