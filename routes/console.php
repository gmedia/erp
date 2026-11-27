<?php

use App\Actions\Approvals\RepairMissingApprovalStepsAction;
use App\Actions\RecurringJournals\ExecuteRecurringJournalAction;
use App\Actions\SecurityLogs\PruneSecurityLogsAction;
use App\Actions\VehicleLoans\CleanupVehicleLoansAction;
use App\Actions\VehicleLoans\NotifyOverdueVehicleLoansAction;
use App\Console\Commands\CleanupSocialLoginTickets;
use App\Console\Commands\PruneStaleDeviceTokens;
use App\Console\Commands\RunSystemHealthChecks;
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

Schedule::command(CleanupSocialLoginTickets::class)
    ->everyFiveMinutes()
    ->name('cleanup-social-login-tickets');

Artisan::command('security-logs:prune', function () {
    $deleted = app(PruneSecurityLogsAction::class)->execute();

    $this->info("Security logs pruned: {$deleted}");

    return Command::SUCCESS;
})->purpose('Delete security logs older than the retention setting');

Schedule::command('security-logs:prune')
    ->daily()
    ->name('prune-security-logs');

Schedule::command(PruneStaleDeviceTokens::class)
    ->daily()
    ->name('prune-stale-device-tokens');

Schedule::command('journals:detect-cross-branch', ['--posted-only'])
    ->weekly()
    ->mondays()
    ->at('06:00');

Artisan::command('vehicle-loans:cleanup {--dry-run : Audit only without deleting data}', function () {
    $stats = app(CleanupVehicleLoansAction::class)->execute((bool) $this->option('dry-run'));

    $this->info(sprintf(
        'Photos deleted: %d. Drafts expired: %d%s',
        $stats['photos_deleted'],
        $stats['drafts_expired'],
        $this->option('dry-run') ? ' (dry run)' : '',
    ));

    return Command::SUCCESS;
})->purpose('Delete expired vehicle-loan photos and cancel stale drafts');

Artisan::command('vehicle-loans:notify-overdue {--dry-run : Audit only without writing data}', function () {
    $stats = app(NotifyOverdueVehicleLoansAction::class)->execute((bool) $this->option('dry-run'));

    $this->info(sprintf(
        'Overdue loans notified: %d%s',
        $stats['notified'],
        $this->option('dry-run') ? ' (dry run)' : '',
    ));

    return Command::SUCCESS;
})->purpose('Notify requesters about overdue vehicle loans');

Schedule::command('vehicle-loans:cleanup')->dailyAt('02:00');
Schedule::command('vehicle-loans:notify-overdue')->everyThirtyMinutes();

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

Schedule::call(fn () => Artisan::call('approvals:repair-missing-steps'))
    ->daily()
    ->name('repair-missing-approval-steps');

// Kesehatan sistem: pemeriksaan tiap 5 menit. Heartbeat scheduler & antrian dipakai cek
// "scheduler terakhir jalan" dan "worker hidup".
Schedule::command(RunSystemHealthChecks::class)
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->name('system-health-run');

Schedule::command('health:schedule-check-heartbeat')
    ->everyMinute()
    ->name('system-health-schedule-heartbeat');

Schedule::command('health:queue-check-heartbeat')
    ->everyFiveMinutes()
    ->name('system-health-queue-heartbeat');

// Backup database harian (arsip di disk 'backups', terpisah dari bucket sumber), lalu cleanup
// retensi bertingkat (config/backup.php). Notifikasi gagal lewat SystemHealthServiceProvider.
Schedule::command('backup:run', ['--only-db'])
    ->dailyAt('01:30')
    ->withoutOverlapping()
    ->name('backup-run-database');

Schedule::command('backup:clean')
    ->dailyAt('02:30')
    ->name('backup-clean');
