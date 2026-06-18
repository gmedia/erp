<?php

namespace App\Console\Commands;

use App\Models\ApPayment;
use App\Models\ArReceipt;
use App\Models\CustomerInvoice;
use App\Models\GoodsReceipt;
use App\Models\JournalEntry;
use App\Models\StockAdjustment;
use App\Models\SupplierBill;
use App\Models\SupplierReturn;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

class BackfillJournalEntryBranch extends Command
{
    /**
     * Source models that resolve a branch directly via their own branch_id column.
     *
     * @var list<class-string<Model>>
     */
    private const DIRECT_BRANCH_SOURCES = [
        ApPayment::class,
        ArReceipt::class,
        CustomerInvoice::class,
        SupplierBill::class,
    ];

    /**
     * Source models that resolve a branch indirectly via warehouse->branch_id (nullable).
     *
     * @var list<class-string<Model>>
     */
    private const WAREHOUSE_BRANCH_SOURCES = [
        GoodsReceipt::class,
        StockAdjustment::class,
        SupplierReturn::class,
    ];

    /**
     * @var string
     */
    protected $signature = 'journals:backfill-branch {--dry-run : Report what would change without writing} {--chunk=500 : Number of rows processed per batch}';

    /**
     * @var string
     */
    protected $description = 'Backfill journal_entries.branch_id from each entry polymorphic source. Idempotent: only touches rows where branch_id is null.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunkSize = max(1, (int) $this->option('chunk'));

        if ($dryRun) {
            $this->warn('DRY RUN — no rows will be written.');
        }

        $resolvers = $this->branchResolvers();

        /** @var array<string, array{matched: int, resolved: int, unresolved: int}> $stats */
        $stats = [];
        $totalUpdated = 0;

        foreach ($resolvers as $sourceType => $resolver) {
            $relation = $this->eagerLoadFor($sourceType);

            $stats[$sourceType] = ['matched' => 0, 'resolved' => 0, 'unresolved' => 0];

            JournalEntry::query()
                ->whereNull('branch_id')
                ->where('source_type', $sourceType)
                ->whereNotNull('source_id')
                ->with($relation)
                ->chunkById($chunkSize, function ($entries) use ($resolver, &$stats, &$totalUpdated, $sourceType, $dryRun): void {
                    foreach ($entries as $entry) {
                        $stats[$sourceType]['matched']++;

                        $source = $entry->source;

                        if (! $source instanceof Model) {
                            $stats[$sourceType]['unresolved']++;

                            continue;
                        }

                        $branchId = $resolver($source);

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
            ->whereNotIn('source_type', array_keys($resolvers))
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
     * Map each branch-bearing source_type (FQCN) to a closure returning its branch id or null.
     *
     * source_type stores FQCNs (no morph map registered); keys are ::class to avoid string literals.
     *
     * @return array<string, callable(Model): ?int>
     */
    private function branchResolvers(): array
    {
        $resolvers = [];

        foreach (self::DIRECT_BRANCH_SOURCES as $sourceClass) {
            $resolvers[$sourceClass] = static fn (Model $source): ?int => $source->getAttribute('branch_id') !== null
                ? (int) $source->getAttribute('branch_id')
                : null;
        }

        foreach (self::WAREHOUSE_BRANCH_SOURCES as $sourceClass) {
            $resolvers[$sourceClass] = static function (Model $source): ?int {
                $warehouse = $source->getAttribute('warehouse');

                if (! $warehouse instanceof Model) {
                    return null;
                }

                return $warehouse->getAttribute('branch_id') !== null
                    ? (int) $warehouse->getAttribute('branch_id')
                    : null;
            };
        }

        return $resolvers;
    }

    /**
     * The eager-load path needed to resolve a branch for the given source_type.
     *
     * @param  class-string<Model>  $sourceType
     * @return list<string>
     */
    private function eagerLoadFor(string $sourceType): array
    {
        if (in_array($sourceType, self::WAREHOUSE_BRANCH_SOURCES, true)) {
            return ['source.warehouse'];
        }

        return ['source'];
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
