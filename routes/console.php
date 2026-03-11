<?php

use App\Actions\Approvals\RepairMissingApprovalStepsAction;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

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
