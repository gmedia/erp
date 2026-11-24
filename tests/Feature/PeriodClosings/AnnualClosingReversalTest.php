<?php

use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Actions\PeriodClosings\ClosePeriodAction;
use App\Actions\PeriodClosings\ReopenPeriodAction;
use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\PeriodClosing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class)->group('period-closings');

/**
 * Jurnal penutup tahunan (#423, langkah 2).
 * - Bertanggal hari terakhir tahun fiskal.
 * - Buka kembali / tutup ulang MEMBALIK jurnal penutup (#375), tidak menghapusnya.
 * - Penutup dan pembaliknya dikecualikan dari guard periode dan tidak masuk laba.
 */
beforeEach(function () {
    $this->fiscalYear = FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);
    $coa = CoaVersion::factory()->create(['status' => 'active']);
    $this->revenue = Account::factory()->create([
        'coa_version_id' => $coa->id, 'is_active' => true, 'type' => 'revenue', 'normal_balance' => 'credit',
    ]);
    $this->expense = Account::factory()->create([
        'coa_version_id' => $coa->id, 'is_active' => true, 'type' => 'expense', 'normal_balance' => 'debit',
    ]);
    $this->retained = Account::factory()->create([
        'coa_version_id' => $coa->id, 'is_active' => true, 'type' => 'equity', 'normal_balance' => 'credit',
    ]);
    $this->cash = Account::factory()->create([
        'coa_version_id' => $coa->id, 'is_active' => true, 'type' => 'asset', 'normal_balance' => 'debit',
    ]);
});

function annualClosingPnl(object $ctx, int $accountId, float $debit, float $credit): void
{
    app(CreateJournalEntryAction::class)->execute([
        'entry_date' => '2026-06-01',
        'description' => 'P&L activity',
        'status' => 'posted',
        'lines' => [
            ['account_id' => $accountId, 'debit' => $debit, 'credit' => $credit, 'memo' => 'fixture'],
            ['account_id' => $ctx->cash->id, 'debit' => $credit, 'credit' => $debit, 'memo' => 'fixture'],
        ],
    ]);
}

function annualClosing(object $ctx): PeriodClosing
{
    return PeriodClosing::factory()->annual()->create([
        'fiscal_year_id' => $ctx->fiscalYear->id,
        'period_year' => 2026,
        'period_month' => null,
        'status' => 'draft',
        'retained_earnings_account_id' => $ctx->retained->id,
    ]);
}

test('jurnal penutup tahunan bertanggal hari terakhir tahun fiskal', function () {
    annualClosingPnl($this, $this->revenue->id, 0, 5000);

    $closing = app(ClosePeriodAction::class)->execute(annualClosing($this));

    expect($closing->closingJournalEntry->entry_date->format('Y-m-d'))->toBe('2026-12-31');
});

test('buka kembali membalik jurnal penutup tanpa menghapusnya', function () {
    annualClosingPnl($this, $this->revenue->id, 0, 5000);
    $closing = app(ClosePeriodAction::class)->execute(annualClosing($this));
    $originalId = $closing->closing_journal_entry_id;

    app(ReopenPeriodAction::class)->execute($closing);

    $original = JournalEntry::find($originalId);
    expect($original)->not->toBeNull();

    $reversal = JournalEntry::where('source_type', PeriodClosing::class)
        ->where('source_id', $closing->id)
        ->where('id', '!=', $originalId)
        ->first();
    expect($reversal)->not->toBeNull()
        ->and($reversal->status)->toBe('posted')
        ->and($reversal->journal_type)->toBe('closing')
        ->and($reversal->entry_date->format('Y-m-d'))->toBe('2026-12-31');

    $originalLines = JournalEntryLine::where('journal_entry_id', $originalId)->get();
    $reversalLines = JournalEntryLine::where('journal_entry_id', $reversal->id)->get();
    foreach ($originalLines as $line) {
        $mirror = $reversalLines->first(
            fn ($r) => $r->account_id === $line->account_id && $r->branch_id === $line->branch_id
        );
        expect((float) $mirror->debit)->toBe((float) $line->credit)
            ->and((float) $mirror->credit)->toBe((float) $line->debit);
    }
});

test('tutup ulang setelah buka kembali tidak menghitung laba penutup dua kali', function () {
    annualClosingPnl($this, $this->revenue->id, 0, 4000);
    $closing = app(ClosePeriodAction::class)->execute(annualClosing($this));

    app(ReopenPeriodAction::class)->execute($closing);
    $closing = app(ClosePeriodAction::class)->execute($closing->refresh());

    expect((float) $closing->net_income)->toBe(4000.0);
});

test('tutup ulang setelah buka kembali menjaga laporan tetap seimbang', function () {
    annualClosingPnl($this, $this->revenue->id, 0, 4000);
    $closing = app(ClosePeriodAction::class)->execute(annualClosing($this));
    app(ReopenPeriodAction::class)->execute($closing);
    $closing = app(ClosePeriodAction::class)->execute($closing->refresh());

    $debit = (float) JournalEntryLine::query()->sum('debit');
    $credit = (float) JournalEntryLine::query()->sum('credit');
    expect(round($debit, 2))->toBe(round($credit, 2));

    $pnlNet = (float) DB::table('journal_entry_lines')
        ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
        ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
        ->where('journal_entries.status', 'posted')
        ->where('journal_entries.journal_type', '<>', 'closing')
        ->whereIn('accounts.type', ['revenue', 'expense'])
        ->selectRaw('COALESCE(SUM(debit - credit), 0) as net')
        ->value('net');
    expect(round($pnlNet, 2))->toBe(-4000.0);
});

test('pembalik jurnal penutup lolos guard walau periode tahunan masih berstatus closed', function () {
    annualClosingPnl($this, $this->revenue->id, 0, 5000);
    $closing = app(ClosePeriodAction::class)->execute(annualClosing($this));
    $closing->refresh();

    // Pembalik bertanggal hari ini (2026, tahun fiskal) sementara periode masih closed.
    // Guard harus mengecualikan jurnal closing; tanpa itu buka kembali akan gagal 422.
    expect(fn () => app(ReopenPeriodAction::class)->execute($closing))->not->toThrow(ValidationException::class);
    $closingEntries = JournalEntry::query()
        ->where('source_type', PeriodClosing::class)
        ->where('source_id', $closing->id)
        ->where('journal_type', 'closing');
    expect($closingEntries->count())->toBe(2);
    expect($closing->refresh()->status)->toBe('reopened');
});

test('periode yang dibuka kembali menerima posting lagi', function () {
    annualClosingPnl($this, $this->revenue->id, 0, 5000);
    $closing = app(ClosePeriodAction::class)->execute(annualClosing($this));
    app(ReopenPeriodAction::class)->execute($closing);

    $entry = app(CreateJournalEntryAction::class)->execute([
        'entry_date' => '2026-10-10',
        'description' => 'setelah dibuka',
        'status' => 'posted',
        'lines' => [
            ['account_id' => $this->cash->id, 'debit' => 10, 'credit' => 0, 'memo' => 'd'],
            ['account_id' => $this->revenue->id, 'debit' => 0, 'credit' => 10, 'memo' => 'c'],
        ],
    ]);

    expect($entry)->toBeInstanceOf(JournalEntry::class);
});
