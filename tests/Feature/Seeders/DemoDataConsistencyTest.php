<?php

use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Support\Demo\DemoCalendar;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\seed;

uses(RefreshDatabase::class)->group('sample-data-seeders');

function glBalance(string $code): float
{
    // Jendela demo 13 bulan lintas tahun fiskal (#571): saldo GL = seluruh FY demo,
    // sebanding dengan subledger yang juga tidak dibatasi FY. Hanya akun CoA aktif
    // yang dihitung: CoA tahun lalu yang diarsipkan sudah dipindahkan ke CoA aktif
    // lewat saldo awal saat penutupan tahun, sehingga menghitung keduanya membuat
    // akun neraca terhitung dua kali.
    $fiscalYearIds = FiscalYear::query()
        ->whereIn('name', array_map('strval', DemoCalendar::fiscalYears()))
        ->pluck('id');

    $coaVersionId = CoaVersion::query()
        ->where('status', 'active')
        ->orderByDesc('fiscal_year_id')
        ->firstOrFail()
        ->id;

    $row = DB::table('journal_entry_lines')
        ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
        ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
        ->whereIn('journal_entries.fiscal_year_id', $fiscalYearIds)
        ->where('journal_entries.status', 'posted')
        ->where('accounts.coa_version_id', $coaVersionId)
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
    // GL dibandingkan dengan subledger hanya melalui jurnal dokumen yang
    // terposting. Seeder tidak boleh memposting jurnal selisih (issue #357).
    seed(DatabaseSeeder::class);

    $outstandingAr = (float) DB::table('customer_invoices')
        ->whereIn('status', ['sent', 'partially_paid', 'overdue'])
        ->where('amount_due', '>', 0)
        ->sum('amount_due');

    $outstandingAp = (float) DB::table('supplier_bills')
        ->whereIn('status', ['confirmed', 'partially_paid', 'overdue'])
        ->where('amount_due', '>', 0)
        ->sum('amount_due');

    // Only in-service assets are capitalised; the GL shows equipment at cost
    // (12100) with a contra accumulated-depreciation account (12200), and the
    // net equals the register book value of in-service assets (W7-05).
    $equipmentCost = (float) DB::table('assets')
        ->whereIn('status', ['active', 'maintenance'])
        ->sum('purchase_cost');
    $accumulatedDepreciation = (float) DB::table('assets')
        ->whereIn('status', ['active', 'maintenance'])
        ->sum('accumulated_depreciation');
    $equipmentBookValue = $equipmentCost - $accumulatedDepreciation;

    expect(glBalance('11200'))->toBe($outstandingAr)
        ->and(-glBalance('21100'))->toBe($outstandingAp)
        ->and(glBalance('12100'))->toBe($equipmentCost)
        ->and(-glBalance('12200'))->toBe($accumulatedDepreciation)
        ->and(glBalance('12100') + glBalance('12200'))->toBe($equipmentBookValue)
        ->and(glBalance('11120'))->toBeGreaterThanOrEqual(0);
});

it('posts no reconciliation plug journals that force GL to equal subledgers', function () {
    seed(DatabaseSeeder::class);

    expect(
        JournalEntry::query()->where('entry_number', 'like', 'REC-%')->count(),
    )->toBe(0);
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
