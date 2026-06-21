<?php

namespace Tests\Feature\Console;

use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Models\Account;
use App\Models\Branch;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
        ->expectsOutputToContain('No economically multi-branch journals detected')
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
        ->expectsOutputToContain('No economically multi-branch journals detected')
        ->assertSuccessful();
});
