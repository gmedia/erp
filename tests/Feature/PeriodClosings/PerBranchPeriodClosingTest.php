<?php

namespace Tests\Feature\PeriodClosings;

use App\Models\Account;
use App\Models\Branch;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\PeriodClosing;
use App\Services\InterBranchClearingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('period-closings');

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['period_closing', 'period_closing.create', 'period_closing.edit']);
    Sanctum::actingAs($this->user, ['*']);

    $this->fiscalYear = FiscalYear::factory()->create([
        'status' => 'open',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);
    $this->revenue = Account::factory()->create(['type' => 'revenue', 'normal_balance' => 'credit']);
    $this->expense = Account::factory()->create(['type' => 'expense', 'normal_balance' => 'debit']);
    $this->retained = Account::factory()->create(['type' => 'equity', 'normal_balance' => 'credit']);
});

function seedPostedPnlLine(object $ctx, int $accountId, ?int $branchId, float $debit, float $credit): void
{
    $entry = JournalEntry::create([
        'fiscal_year_id' => $ctx->fiscalYear->id,
        'entry_number' => 'JV-' . uniqid(),
        'entry_date' => '2026-06-01',
        'description' => 'P&L activity',
        'status' => 'posted',
        'journal_type' => 'general',
        'branch_id' => $branchId,
        'created_by' => $ctx->user->id,
        'posted_by' => $ctx->user->id,
        'posted_at' => now(),
    ]);
    JournalEntryLine::create([
        'journal_entry_id' => $entry->id,
        'account_id' => $accountId,
        'branch_id' => $branchId,
        'debit' => $debit,
        'credit' => $credit,
        'memo' => 'fixture',
    ]);
}

function makeAnnualClosing(object $ctx): PeriodClosing
{
    return PeriodClosing::factory()->annual()->create([
        'fiscal_year_id' => $ctx->fiscalYear->id,
        'period_year' => 2026,
        'status' => 'draft',
        'retained_earnings_account_id' => $ctx->retained->id,
    ]);
}

test('multi-branch annual close balances each branch with per-branch retained earnings', function () {
    $branchA = Branch::factory()->create();
    $branchB = Branch::factory()->create();

    seedPostedPnlLine($this, $this->revenue->id, $branchA->id, 0, 5000);
    seedPostedPnlLine($this, $this->revenue->id, $branchB->id, 0, 3000);
    seedPostedPnlLine($this, $this->expense->id, $branchB->id, 1000, 0);

    $closing = makeAnnualClosing($this);
    postJson("/api/period-closings/{$closing->id}/close")->assertOk();

    $closing->refresh();
    $lines = JournalEntryLine::where('journal_entry_id', $closing->closing_journal_entry_id)->get();

    foreach ([$branchA->id, $branchB->id] as $branchId) {
        $branchLines = $lines->where('branch_id', $branchId);
        $debit = $branchLines->sum(fn ($l) => (int) round(((float) $l->debit) * 100));
        $credit = $branchLines->sum(fn ($l) => (int) round(((float) $l->credit) * 100));
        expect($debit)->toBe($credit);
    }

    $reA = $lines->where('branch_id', $branchA->id)->firstWhere('account_id', $this->retained->id);
    $reB = $lines->where('branch_id', $branchB->id)->firstWhere('account_id', $this->retained->id);
    expect(round((float) $reA->credit, 2))->toBe(5000.0);
    expect(round((float) $reB->credit, 2))->toBe(2000.0);

    expect(round((float) $closing->net_income, 2))->toBe(7000.0);

    $clearing = collect($lines)->filter(fn ($l) => $l->memo === InterBranchClearingService::CLEARING_MEMO);
    expect($clearing)->toHaveCount(0);
});

test('all-null single-branch close produces one null-branch retained earnings line', function () {
    seedPostedPnlLine($this, $this->revenue->id, null, 0, 5000);
    seedPostedPnlLine($this, $this->expense->id, null, 2000, 0);

    $closing = makeAnnualClosing($this);
    postJson("/api/period-closings/{$closing->id}/close")->assertOk();

    $closing->refresh();
    $lines = JournalEntryLine::where('journal_entry_id', $closing->closing_journal_entry_id)->get();

    expect($lines->whereNotNull('branch_id'))->toHaveCount(0);
    $re = $lines->firstWhere('account_id', $this->retained->id);
    expect(round((float) $re->credit, 2))->toBe(3000.0);
    expect(round((float) $closing->net_income, 2))->toBe(3000.0);
});

test('reopen then reclose does not double-count and leaves one closing entry', function () {
    seedPostedPnlLine($this, $this->revenue->id, null, 0, 4000);

    $closing = makeAnnualClosing($this);
    postJson("/api/period-closings/{$closing->id}/close")->assertOk();
    $closing->refresh();
    $firstEntryId = $closing->closing_journal_entry_id;

    postJson("/api/period-closings/{$closing->id}/reopen")->assertOk();
    expect(JournalEntry::find($firstEntryId))->toBeNull();

    postJson("/api/period-closings/{$closing->id}/close")->assertOk();
    $closing->refresh();

    expect(round((float) $closing->net_income, 2))->toBe(4000.0);
    expect(JournalEntry::where('source_type', PeriodClosing::class)->where('source_id', $closing->id)->count())->toBe(1);
});
