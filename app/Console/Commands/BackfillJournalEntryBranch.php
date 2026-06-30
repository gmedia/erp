<?php

namespace App\Console\Commands;

use App\Domain\Branch\BranchResolverRegistry;
use App\Models\JournalEntry;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

class BackfillJournalEntryBranch extends Command
{
    /**
     * @var string
     */
    protected $signature = 'journals:backfill-branch'
        . ' {--dry-run : Report what would change without writing}'
        . ' {--chunk=500 : Number of rows processed per batch}';

    /**
     * @var string
     */
    protected $description = 'Backfill journal_entries.branch_id from each entry'
        . ' polymorphic source. Idempotent: only touches rows where branch_id is null.';

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

        foreach ($branchBearingTypes as $sourceType) {
            $relation = array_map(
                static fn (string $relation): string => "source.{$relation}",
                $registry->relationsFor($sourceType),
            );

            $stats[$sourceType] = ['matched' => 0, 'resolved' => 0, 'unresolved' => 0];

            JournalEntry::query()
                ->whereNull('branch_id')
                ->where('source_type', $sourceType)
                ->whereNotNull('source_id')
                ->with(array_merge(['source'], $relation))
                ->chunkById($chunkSize,
                    function ($entries) use ($registry, &$stats, &$totalUpdated, $sourceType, $dryRun): void {
                        foreach ($entries as $entry) {
                            $stats[$sourceType]['matched']++;

                            $source = $entry->source;

                            if (! $source instanceof Model) {
                                $stats[$sourceType]['unresolved']++;

                                continue;
                            }

                            $branchId = $registry->resolve($source);

                            if ($branchId === null) {
                                $stats[$sourceType]['unresolved']++;

                                continue;
                            }

                            $stats[$sourceType]['resolved']++;

                            if (! $dryRun) {
                                $entry->branch_id = $branchId;
                                $entry->save();
                            }

                            $totalUpdated++;
                        }
                    });
        }

        $this->renderSummary($stats, $dryRun);

        $nullSkipped = JournalEntry::query()
            ->whereNull('branch_id')
            ->whereNotIn('source_type', $branchBearingTypes)
            ->count();

        $this->line('');
        $this->info(sprintf(
            '%s %d journal entries from branch-bearing sources.',
            $dryRun ? 'Would update' : 'Updated',
            $totalUpdated,
        ));
        $this->line(sprintf(
            '%d entries left null by design (no-branch sources or null source_type).',
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

        foreach ($stats as $sourceType => $counts) {
            if ($counts['matched'] === 0) {
                continue;
            }

            $rows[] = [
                class_basename($sourceType),
                $counts['matched'],
                $counts['resolved'],
                $counts['unresolved'],
            ];
        }

        if ($rows === []) {
            $this->line('No null-branch journal entries from branch-bearing sources found.');

            return;
        }

        $this->table(
            ['Source', 'Matched', $dryRun ? 'Would set' : 'Set', 'Left null'],
            $rows,
        );
    }
}
