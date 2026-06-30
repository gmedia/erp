<?php

namespace App\Console\Commands;

use App\Services\InterBranchClearingService;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Sentry\Severity;
use Sentry\State\Scope;

use function Sentry\captureMessage;
use function Sentry\withScope;

class DetectCrossBranchJournals extends Command
{
    /**
     * @var string
     */
    protected $signature = 'journals:detect-cross-branch'
        . ' {--posted-only : Count only posted journal entries}'
        . ' {--limit=20 : Max number of sample entry numbers to list}';

    /**
     * @var string
     */
    protected $description = 'Report how many journal entries are economically multi-branch'
        . ' and how many inter-branch clearing lines exist.'
        . ' Read-only gate for the deferred retro-correction work (full 2b PR8).';

    public function handle(): int
    {
        $postedOnly = (bool) $this->option('posted-only');
        $limit = max(1, (int) $this->option('limit'));

        $entryFilter = function ($query) use ($postedOnly): void {
            if ($postedOnly) {
                $query->where('journal_entries.status', 'posted');
            }
        };

        $multiBranchEntryIds = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->whereNotNull('journal_entry_lines.branch_id')
            ->where($entryFilter)
            ->select('journal_entry_lines.journal_entry_id')
            ->groupBy('journal_entry_lines.journal_entry_id')
            ->havingRaw('COUNT(DISTINCT journal_entry_lines.branch_id) > 1')
            ->pluck('journal_entry_lines.journal_entry_id');

        $multiBranchCount = $multiBranchEntryIds->count();

        $clearingLineCount = $this->clearingLineQuery($postedOnly)->count('journal_entry_lines.id');

        $clearingEntryCount = $this->clearingLineQuery($postedOnly)
            ->distinct()
            ->count('journal_entry_lines.journal_entry_id');

        $nullBranchLineCount = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->whereNull('journal_entry_lines.branch_id')
            ->where($entryFilter)
            ->count('journal_entry_lines.id');

        $this->renderSummary(
            $postedOnly,
            $multiBranchCount,
            $clearingEntryCount,
            $clearingLineCount,
            $nullBranchLineCount,
        );

        if ($multiBranchCount > 0) {
            $sampleNumbers = DB::table('journal_entries')
                ->whereIn('id', $multiBranchEntryIds)
                ->orderBy('entry_date')
                ->limit($limit)
                ->pluck('entry_number');

            $this->line('');
            $this->line('Sample multi-branch entries:');
            foreach ($sampleNumbers as $number) {
                $this->line("  - {$number}");
            }
        }

        $this->line('');
        if ($multiBranchCount === 0) {
            $this->info('No economically multi-branch journals detected. Retro-correction (PR8) is NOT warranted.');
        } else {
            $this->warn(sprintf(
                '%d multi-branch journal entries detected. Evaluate whether retro-correction (PR8) is now warranted.',
                $multiBranchCount,
            ));

            Log::warning('Cross-branch journals detected by scheduled monitor.', [
                'scope' => $postedOnly ? 'posted' : 'all',
                'multi_branch_entries' => $multiBranchCount,
                'clearing_entries' => $clearingEntryCount,
                'clearing_lines' => $clearingLineCount,
                'null_branch_lines' => $nullBranchLineCount,
            ]);

            withScope(function (
                Scope $scope,
            ) use (
                $postedOnly,
                $multiBranchCount,
                $clearingEntryCount,
                $clearingLineCount,
                $nullBranchLineCount,
            ): void {
                $scope->setContext('cross_branch_monitor', [
                    'scope' => $postedOnly ? 'posted' : 'all',
                    'multi_branch_entries' => $multiBranchCount,
                    'clearing_entries' => $clearingEntryCount,
                    'clearing_lines' => $clearingLineCount,
                    'null_branch_lines' => $nullBranchLineCount,
                ]);

                captureMessage(
                    sprintf('Cross-branch journals detected by scheduled monitor: %d multi-branch entries.', $multiBranchCount),
                    Severity::warning(),
                );
            });
        }

        return self::SUCCESS;
    }

    private function clearingLineQuery(bool $postedOnly): Builder
    {
        $query = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
            ->where('accounts.code', InterBranchClearingService::CLEARING_CODE);

        if ($postedOnly) {
            $query->where('journal_entries.status', 'posted');
        }

        return $query;
    }

    private function renderSummary(
        bool $postedOnly,
        int $multiBranchCount,
        int $clearingEntryCount,
        int $clearingLineCount,
        int $nullBranchLineCount,
    ): void {
        $this->info($postedOnly
            ? 'Scope: posted journal entries only.'
            : 'Scope: all journal entries (draft + posted).');

        $this->table(
            ['Metric', 'Count'],
            [
                ['Economically multi-branch entries', $multiBranchCount],
                ['Entries with clearing lines ('
                    . InterBranchClearingService::CLEARING_CODE . ')', $clearingEntryCount],
                ['Total clearing lines', $clearingLineCount],
                ['Null-branch lines (company-wide)', $nullBranchLineCount],
            ],
        );
    }
}
