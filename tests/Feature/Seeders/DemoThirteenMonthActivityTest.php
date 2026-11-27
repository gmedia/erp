<?php

use App\Models\ApPayment;
use App\Models\ArReceipt;
use App\Models\CustomerInvoice;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\SupplierBill;
use App\Support\Demo\DemoCalendar;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\seed;

uses(RefreshDatabase::class)->group('sample-data-seeders');

/**
 * Jumlah jurnal posted per bulan jendela demo (#571).
 *
 * @return array<string, int> kunci 'YYYY-MM'
 */
function postedJournalsByMonth(): array
{
    return collect(DemoCalendar::months())
        ->mapWithKeys(fn ($month) => [
            $month->format('Y-m') => JournalEntry::query()
                ->where('status', 'posted')
                ->whereBetween('entry_date', [
                    $month->copy()->startOfMonth()->toDateString(),
                    $month->copy()->endOfMonth()->toDateString(),
                ])
                ->count(),
        ])
        ->all();
}

it('has at least one posted journal in every month of the 13-month demo window', function () {
    seed(DatabaseSeeder::class);

    $perMonth = postedJournalsByMonth();

    expect($perMonth)->toHaveCount(DemoCalendar::MONTHS);

    foreach ($perMonth as $month => $count) {
        expect($count)->toBeGreaterThan(0, "Bulan {$month} tidak punya jurnal posted");
    }
});

it('has invoices, bills, receipts and payments in every month of the window', function () {
    seed(DatabaseSeeder::class);

    foreach (DemoCalendar::months() as $month) {
        $start = $month->copy()->startOfMonth()->toDateString();
        $end = $month->copy()->endOfMonth()->toDateString();

        expect(CustomerInvoice::query()->whereBetween('invoice_date', [$start, $end])->count())
            ->toBeGreaterThan(0, "Bulan {$month->format('Y-m')} tanpa invoice")
            ->and(SupplierBill::query()->whereBetween('bill_date', [$start, $end])->count())
            ->toBeGreaterThan(0, "Bulan {$month->format('Y-m')} tanpa tagihan supplier")
            ->and(ArReceipt::query()->whereBetween('receipt_date', [$start, $end])->count())
            ->toBeGreaterThan(0, "Bulan {$month->format('Y-m')} tanpa penerimaan")
            ->and(ApPayment::query()->whereBetween('payment_date', [$start, $end])->count())
            ->toBeGreaterThan(0, "Bulan {$month->format('Y-m')} tanpa pembayaran");
    }
});

it('keeps the GL accounts receivable and payable equal to their subledgers', function () {
    seed(DatabaseSeeder::class);

    $arSubledger = (float) CustomerInvoice::query()
        ->whereIn('status', ['sent', 'partially_paid', 'paid', 'overdue'])
        ->sum('amount_due');
    $apSubledger = (float) SupplierBill::query()
        ->whereIn('status', ['confirmed', 'partially_paid', 'paid', 'overdue'])
        ->sum('amount_due');

    $arGl = glNetForCode('11200', 'debit');
    $apGl = glNetForCode('21100', 'credit');

    expect(round($arGl, 2))->toBe(round($arSubledger, 2))
        ->and(round($apGl, 2))->toBe(round($apSubledger, 2));
});

it('keeps the posted ledger balanced across the whole window', function () {
    seed(DatabaseSeeder::class);

    $totals = DB::table('journal_entry_lines')
        ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
        ->where('journal_entries.status', 'posted')
        ->selectRaw('COALESCE(SUM(journal_entry_lines.debit),0) as dr, COALESCE(SUM(journal_entry_lines.credit),0) as cr')
        ->first();

    expect(round((float) $totals->dr, 2))->toBe(round((float) $totals->cr, 2));
});

it('keeps the monthly AR and AP demo seeders idempotent', function () {
    seed(DatabaseSeeder::class);

    $tables = ['customer_invoices', 'ar_receipts', 'supplier_bills', 'ap_payments', 'journal_entries'];
    $before = collect($tables)->mapWithKeys(fn (string $t) => [$t => DB::table($t)->count()])->all();
    $monthsBefore = postedJournalsByMonth();

    seed(DatabaseSeeder::class);

    $after = collect($tables)->mapWithKeys(fn (string $t) => [$t => DB::table($t)->count()])->all();

    expect($after)->toBe($before)
        ->and(postedJournalsByMonth())->toBe($monthsBefore);
});

it('keeps every fiscal year of the demo window closed or open as it was before posting', function () {
    seed(DatabaseSeeder::class);

    // FY tahun lalu harus kembali closed setelah transaksi bulanan diposting (#571).
    expect(FiscalYear::query()->where('name', '2025')->value('status'))->toBe('closed')
        ->and(FiscalYear::query()->where('name', '2026')->value('status'))->toBe('open');
});

/**
 * Saldo GL satu akun aktif (COA aktif) dari jurnal posted.
 * $side 'debit' = dr-cr, 'credit' = cr-dr.
 */
function glNetForCode(string $code, string $side): float
{
    $row = DB::table('journal_entry_lines')
        ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
        ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
        ->join('coa_versions', 'coa_versions.id', '=', 'accounts.coa_version_id')
        ->where('coa_versions.status', 'active')
        ->where('journal_entries.status', 'posted')
        ->where('accounts.code', $code)
        ->selectRaw('COALESCE(SUM(journal_entry_lines.debit),0) as dr, COALESCE(SUM(journal_entry_lines.credit),0) as cr')
        ->first();

    return $side === 'debit'
        ? (float) $row->dr - (float) $row->cr
        : (float) $row->cr - (float) $row->dr;
}
