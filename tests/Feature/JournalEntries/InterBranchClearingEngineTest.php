<?php

namespace Tests\Feature\JournalEntries;

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
    $this->clearingId = (int) $this->accountMap['1999-IBC'];

    $this->user = User::firstOrFail();
    $this->branchA = Branch::factory()->create(['name' => 'Branch A']);
    $this->branchB = Branch::factory()->create(['name' => 'Branch B']);

    $this->action = app(CreateJournalEntryAction::class);
});

test('single-branch posted entry persists no clearing line (dormant)', function () {
    $entry = $this->action->execute([
        'entry_date' => '2026-02-01',
        'description' => 'Single-branch entry',
        'status' => 'posted',
        'branch_id' => $this->branchA->id,
        'lines' => [
            ['account_id' => $this->accountMap['11110'], 'debit' => 1000, 'credit' => 0],
            ['account_id' => $this->accountMap['41000'], 'debit' => 0, 'credit' => 1000],
        ],
    ]);

    $lines = $entry->lines()->get();
    expect($lines)->toHaveCount(2);
    expect($lines->where('account_id', $this->clearingId))->toHaveCount(0);
    expect($lines->every(fn ($l) => (int) $l->branch_id === $this->branchA->id))->toBeTrue();
});

test('multi-branch posted entry injects per-branch clearing lines', function () {
    $entry = $this->action->execute([
        'entry_date' => '2026-02-01',
        'description' => 'Inter-branch cash transfer A -> B',
        'status' => 'posted',
        'lines' => [
            ['account_id' => $this->accountMap['11110'], 'branch_id' => $this->branchA->id, 'debit' => 0, 'credit' => 500],
            ['account_id' => $this->accountMap['11110'], 'branch_id' => $this->branchB->id, 'debit' => 500, 'credit' => 0],
        ],
    ]);

    $lines = $entry->lines()->get();

    $clearing = $lines->where('account_id', $this->clearingId);
    expect($clearing)->toHaveCount(2);

    foreach ([$this->branchA->id, $this->branchB->id] as $branchId) {
        $branchLines = $lines->where('branch_id', $branchId);
        $debit = $branchLines->sum(fn ($l) => (int) round(((float) $l->debit) * 100));
        $credit = $branchLines->sum(fn ($l) => (int) round(((float) $l->credit) * 100));
        expect($debit)->toBe($credit);
    }

    $clearingNet = $clearing->sum(fn ($l) => (int) round(((float) $l->debit) * 100) - (int) round(((float) $l->credit) * 100));
    expect($clearingNet)->toBe(0);
});

test('multi-branch entry remains globally balanced after injection', function () {
    $entry = $this->action->execute([
        'entry_date' => '2026-02-01',
        'description' => 'HQ centralized payment for A and B',
        'status' => 'posted',
        'branch_id' => null,
        'lines' => [
            ['account_id' => $this->accountMap['11110'], 'branch_id' => $this->branchA->id, 'debit' => 0, 'credit' => 300],
            ['account_id' => $this->accountMap['52000'], 'branch_id' => $this->branchA->id, 'debit' => 100, 'credit' => 0],
            ['account_id' => $this->accountMap['52000'], 'branch_id' => $this->branchB->id, 'debit' => 200, 'credit' => 0],
        ],
    ]);

    $lines = $entry->lines()->get();
    $totalDebit = $lines->sum(fn ($l) => (int) round(((float) $l->debit) * 100));
    $totalCredit = $lines->sum(fn ($l) => (int) round(((float) $l->credit) * 100));
    expect($totalDebit)->toBe($totalCredit);
});
