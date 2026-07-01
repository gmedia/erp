<?php

namespace Tests\Feature\Console;

use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Models\Account;
use App\Models\Branch;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Sentry\ClientInterface;
use Sentry\SentrySdk;

use function Pest\Laravel\seed;

uses(RefreshDatabase::class)->group('inter-branch-clearing');

beforeEach(function () {
    seed();

    $this->fiscalYear = FiscalYear::where('status', 'open')->firstOrFail();
    /** @var CoaVersion $coaVersion */
    $coaVersion = $this->fiscalYear->coaVersions()->where('status', 'active')->firstOrFail();
    $this->accountMap = Account::where('coa_version_id', $coaVersion->id)
        ->pluck('id', 'code')
        ->toArray();

    $this->user = User::firstOrFail();
    $this->branchA = Branch::factory()->create(['name' => 'Branch A']);
    $this->branchB = Branch::factory()->create(['name' => 'Branch B']);
    $this->action = app(CreateJournalEntryAction::class);
});

test('detects zero multi-branch journals on single-branch data', function () {
    $this->action->execute([
        'entry_date' => '2026-02-01',
        'description' => 'Single-branch entry',
        'status' => 'posted',
        'branch_id' => $this->branchA->id,
        'lines' => [
            ['account_id' => $this->accountMap['11110'], 'debit' => 1000, 'credit' => 0],
            ['account_id' => $this->accountMap['41000'], 'debit' => 0, 'credit' => 1000],
        ],
    ]);

    $this->artisan('journals:detect-cross-branch')
        ->expectsOutputToContain('No multi-branch journals. Retro-correction (PR8) NOT warranted.')
        ->assertSuccessful();
});

test('detects multi-branch journals and clearing lines once a cross-branch entry exists', function () {
    $this->action->execute([
        'entry_date' => '2026-02-01',
        'description' => 'Inter-branch cash transfer A -> B',
        'status' => 'posted',
        'lines' => [
            ['account_id' => $this->accountMap['11110'], 'branch_id' => $this->branchA->id, 'debit' => 0, 'credit' => 500],
            ['account_id' => $this->accountMap['11110'], 'branch_id' => $this->branchB->id, 'debit' => 500, 'credit' => 0],
        ],
    ]);

    $this->artisan('journals:detect-cross-branch')
        ->expectsOutputToContain('Evaluate whether retro-correction')
        ->assertSuccessful();
});

test('posted-only scope ignores draft cross-branch journals', function () {
    $this->action->execute([
        'entry_date' => '2026-02-01',
        'description' => 'Draft inter-branch entry',
        'status' => 'draft',
        'lines' => [
            ['account_id' => $this->accountMap['11110'], 'branch_id' => $this->branchA->id, 'debit' => 0, 'credit' => 500],
            ['account_id' => $this->accountMap['11110'], 'branch_id' => $this->branchB->id, 'debit' => 500, 'credit' => 0],
        ],
    ]);

    $this->artisan('journals:detect-cross-branch --posted-only')
        ->expectsOutputToContain('No multi-branch journals. Retro-correction (PR8) NOT warranted.')
        ->assertSuccessful();
});

test('logs a warning when multi-branch journals are detected', function () {
    Log::spy();

    $this->action->execute([
        'entry_date' => '2026-02-01',
        'description' => 'Inter-branch cash transfer A -> B',
        'status' => 'posted',
        'lines' => [
            ['account_id' => $this->accountMap['11110'], 'branch_id' => $this->branchA->id, 'debit' => 0, 'credit' => 500],
            ['account_id' => $this->accountMap['11110'], 'branch_id' => $this->branchB->id, 'debit' => 500, 'credit' => 0],
        ],
    ]);

    $this->artisan('journals:detect-cross-branch')->assertSuccessful();

    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(function (string $message, array $context): bool {
            return str_contains($message, 'Cross-branch journals detected')
                && $context['multi_branch_entries'] >= 1;
        });
});

test('does not log a warning when no multi-branch journals exist', function () {
    Log::spy();

    $this->action->execute([
        'entry_date' => '2026-02-01',
        'description' => 'Single-branch entry',
        'status' => 'posted',
        'branch_id' => $this->branchA->id,
        'lines' => [
            ['account_id' => $this->accountMap['11110'], 'debit' => 1000, 'credit' => 0],
            ['account_id' => $this->accountMap['41000'], 'debit' => 0, 'credit' => 1000],
        ],
    ]);

    $this->artisan('journals:detect-cross-branch')->assertSuccessful();

    Log::shouldNotHaveReceived('warning');
});

test('captures a Sentry message when multi-branch journals are detected', function () {
    $hub = SentrySdk::getCurrentHub();
    $originalClient = $hub->getClient();

    $captured = [];
    $mockClient = mock(ClientInterface::class)->makePartial();
    $mockClient->shouldReceive('captureMessage')
        ->andReturnUsing(function (string $message) use (&$captured) {
            $captured[] = $message;

            return null;
        });

    $hub->bindClient($mockClient);

    try {
        $this->action->execute([
            'entry_date' => '2026-02-01',
            'description' => 'Inter-branch cash transfer A -> B',
            'status' => 'posted',
            'lines' => [
                ['account_id' => $this->accountMap['11110'], 'branch_id' => $this->branchA->id, 'debit' => 0, 'credit' => 500],
                ['account_id' => $this->accountMap['11110'], 'branch_id' => $this->branchB->id, 'debit' => 500, 'credit' => 0],
            ],
        ]);

        $this->artisan('journals:detect-cross-branch')->assertSuccessful();
    } finally {
        $hub->bindClient($originalClient);
    }

    expect($captured)->toHaveCount(1)
        ->and($captured[0])->toContain('Cross-branch journals detected by scheduled monitor');
});
