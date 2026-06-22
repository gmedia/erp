<?php

use App\Actions\Approvals\RepairMissingApprovalStepsAction;
use App\Actions\RecurringJournals\ExecuteRecurringJournalAction;
use App\Models\RecurringJournal;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('recurring-journals:execute', function () {
    $dueJournals = RecurringJournal::due()->get();

    if ($dueJournals->isEmpty()) {
        $this->info('No recurring journals due for execution.');

        return Command::SUCCESS;
    }

    $action = app(ExecuteRecurringJournalAction::class);
    $executed = 0;
    $failed = 0;

    foreach ($dueJournals as $journal) {
        try {
            $action->execute($journal);
            $executed++;
            $this->line("  ✓ Executed: {$journal->name} (#{$journal->id})");
        } catch (Exception $e) {
            $failed++;
            $this->error("  ✗ Failed: {$journal->name} (#{$journal->id}) — {$e->getMessage()}");
            Log::error('Recurring journal execution failed', [
                'recurring_journal_id' => $journal->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    $this->info("Done. Executed: {$executed}, Failed: {$failed}");

    return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
})->purpose('Execute all due recurring journals');

Schedule::command('recurring-journals:execute')->daily();

Schedule::command('journals:detect-cross-branch', ['--posted-only'])
    ->weekly()
    ->mondays()
    ->at('06:00');

Artisan::command('approvals:repair-missing-steps {--dry-run : Audit only without writing data}', function () {
    $report = app(RepairMissingApprovalStepsAction::class)->execute(
        (bool) $this->option('dry-run'),
    );

    if ($report['total'] === 0) {
        $this->info('No approval requests missing steps were found.');

        return Command::SUCCESS;
    }

    $this->info(
        sprintf(
            'Found %d approval request(s) missing steps.',
            $report['total'],
        ),
    );

    $this->table(
        [
            'Request ID',
            'Flow ID',
            'Flow Code',
            'Status',
            'Current Step',
            'Flow Steps',
            'Outcome',
        ],
        array_map(
            static fn (array $item) => [
                $item['approval_request_id'],
                $item['approval_flow_id'],
                $item['approval_flow_code'],
                $item['status'],
                $item['current_step_order'],
                $item['flow_step_count'],
                $item['outcome'],
            ],
            $report['items'],
        ),
    );

    $this->line('');
    $this->info(sprintf('Repaired: %d', $report['repaired']));
    $this->warn(sprintf('Skipped: %d', $report['skipped']));
    $this->line(
        $report['dry_run']
            ? 'Dry run only. Re-run without --dry-run to apply the repair.'
            : 'Repair completed.',
    );

    return Command::SUCCESS;
})->purpose('Audit or repair approval requests that are missing request steps');
