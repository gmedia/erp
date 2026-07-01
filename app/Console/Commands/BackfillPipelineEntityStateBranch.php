<?php

namespace App\Console\Commands;

use App\Domain\Branch\BranchResolverRegistry;
use App\Models\PipelineEntityState;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

class BackfillPipelineEntityStateBranch extends Command
{
    /**
     * @var string
     */
    protected $signature = 'pipeline-states:backfill-branch'
        . ' {--dry-run : Report what would change without writing}'
        . ' {--chunk=500 : Number of rows processed per batch}';

    protected $description = 'Backfill pipeline_entity_states.branch_id'
        . ' from each row polymorphic entity.'
        . ' Idempotent: only touches rows where branch_id is null.';

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

        foreach ($branchBearingTypes as $entityType) {
            $relation = array_map(
                static fn (string $relation): string => "entity.{$relation}",
                $registry->relationsFor($entityType),
            );

            $stats[$entityType] = ['matched' => 0, 'resolved' => 0, 'unresolved' => 0];

            PipelineEntityState::query()
                ->whereNull('branch_id')
                ->where('entity_type', $entityType)
                ->with(array_merge(['entity'], $relation))
                ->chunkById($chunkSize,
                    function ($states) use ($registry, &$stats, &$totalUpdated, $entityType, $dryRun): void {
                        foreach ($states as $state) {
                            $stats[$entityType]['matched']++;

                            $entity = $state->entity;

                            if (! $entity instanceof Model) {
                                $stats[$entityType]['unresolved']++;

                                continue;
                            }

                            $branchId = $registry->resolve($entity);

                            if ($branchId === null) {
                                $stats[$entityType]['unresolved']++;

                                continue;
                            }

                            $stats[$entityType]['resolved']++;

                            if (! $dryRun) {
                                $state->branch_id = $branchId;
                                $state->save();
                            }

                            $totalUpdated++;
                        }
                    });
        }

        $this->renderSummary($stats, $dryRun);

        $nullSkipped = PipelineEntityState::query()
            ->whereNull('branch_id')
            ->whereNotIn('entity_type', $branchBearingTypes)
            ->count();

        $this->line('');
        $this->info(sprintf(
            '%s %d pipeline entity states from branch-bearing entities.',
            $dryRun ? 'Would update' : 'Updated',
            $totalUpdated,
        ));
        $this->line(sprintf(
            '%d states left null by design (no-branch entities or unregistered entity_type).',
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

        foreach ($stats as $entityType => $counts) {
            if ($counts['matched'] === 0) {
                continue;
            }

            $rows[] = [
                class_basename($entityType),
                $counts['matched'],
                $counts['resolved'],
                $counts['unresolved'],
            ];
        }

        if ($rows === []) {
            $this->line('No null-branch pipeline entity states from branch-bearing entities found.');

            return;
        }

        $this->table(
            ['Entity', 'Matched', $dryRun ? 'Would set' : 'Set', 'Left null'],
            $rows,
        );
    }
}
