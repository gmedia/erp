<?php

use App\Models\FiscalYear;
use App\Models\JournalEntry;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\SubledgerGlReconciliationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\seed;

uses(RefreshDatabase::class)->group('sample-data-seeders');

function glBalance(string $code): float
{
    $fiscalYear = FiscalYear::query()->where('status', 'open')->firstOrFail();

    $row = DB::table('journal_entry_lines')
        ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
        ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
        ->where('journal_entries.fiscal_year_id', $fiscalYear->id)
        ->where('journal_entries.status', 'posted')
        ->where('accounts.code', $code)
        ->selectRaw('COALESCE(SUM(journal_entry_lines.debit),0) as debit, COALESCE(SUM(journal_entry_lines.credit),0) as credit')
        ->first();

    return (float) $row->debit - (float) $row->credit;
}

it('keeps demo seed idempotent across repeated runs', function () {
    seed(DatabaseSeeder::class);

    $tables = [
        'suppliers', 'customers', 'products', 'assets', 'budgets',
        'purchase_orders', 'customer_invoices', 'supplier_bills',
        'journal_entries', 'journal_entry_lines', 'employees',
        'stock_movements', 'period_closings',
    ];

    $before = collect($tables)->mapWithKeys(
        fn (string $table) => [$table => DB::table($table)->count()],
    )->all();

    seed(DatabaseSeeder::class);

    $after = collect($tables)->mapWithKeys(
        fn (string $table) => [$table => DB::table($table)->count()],
    )->all();

    expect($after)->toBe($before);
});

it('never seeds a due date earlier than the document date', function () {
    seed(DatabaseSeeder::class);

    expect(
        DB::table('supplier_bills')->whereColumn('due_date', '<', 'bill_date')->count(),
    )->toBe(0)->and(
        DB::table('customer_invoices')->whereColumn('due_date', '<', 'invoice_date')->count(),
    )->toBe(0);
});

it('reconciles GL balances with AR AP inventory and fixed asset subledgers', function () {
    seed(DatabaseSeeder::class);
    seed(SubledgerGlReconciliationSeeder::class);

    $outstandingAr = (float) DB::table('customer_invoices')
        ->whereIn('status', ['sent', 'partially_paid', 'overdue'])
        ->where('amount_due', '>', 0)
        ->sum('amount_due');

    $outstandingAp = (float) DB::table('supplier_bills')
        ->whereIn('status', ['confirmed', 'partially_paid', 'overdue'])
        ->where('amount_due', '>', 0)
        ->sum('amount_due');

    $equipmentBookValue = (float) DB::table('assets')->sum('book_value');

    expect(glBalance('11200'))->toBe($outstandingAr)
        ->and(-glBalance('21100'))->toBe($outstandingAp)
        ->and(glBalance('12100'))->toBe($equipmentBookValue)
        ->and(glBalance('11120'))->toBeGreaterThanOrEqual(0);
});

it('keeps the system inter-branch clearing account code available', function () {
    // 1999-IBC adalah kode sistem yang dirujuk clearing engine, bukan akun
    // bisnis biasa — sengaja dipertahankan (W5-22: not a bug).
    seed(DatabaseSeeder::class);

    expect(DB::table('accounts')->where('code', '1999-IBC')->count())->toBeGreaterThan(0);
});

it('keeps every seeded journal entry balanced', function () {
    seed(DatabaseSeeder::class);

    $unbalanced = DB::table('journal_entry_lines')
        ->selectRaw('journal_entry_id, SUM(debit) - SUM(credit) as diff')
        ->groupBy('journal_entry_id')
        ->havingRaw('ABS(SUM(debit) - SUM(credit)) > 0.01')
        ->pluck('journal_entry_id');

    expect($unbalanced->all())->toBe([]);
    expect(JournalEntry::query()->count())->toBeGreaterThan(0);
});
