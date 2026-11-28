<?php

use App\Actions\AccountingPosting\ResolveControlAccountAction;
use App\Models\Account;
use App\Models\AccountMapping;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use Database\Seeders\CoaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class)->group('resolve-control-account');

/**
 * Membuat satu FY + COA dengan status tertentu dan akun kontrol 11110.
 */
function makeCoaForYear(string $year, string $coaStatus, string $accountName): CoaVersion
{
    $fiscalYear = FiscalYear::factory()->create([
        'name' => $year,
        'start_date' => $year . '-01-01',
        'end_date' => $year . '-12-31',
        'status' => 'open',
    ]);

    $coa = CoaVersion::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'status' => $coaStatus,
    ]);

    Account::factory()->create([
        'coa_version_id' => $coa->id,
        'code' => '11110',
        'name' => $accountName,
        'type' => 'asset',
        'normal_balance' => 'debit',
        'is_active' => true,
    ]);

    return $coa;
}

test('resolver memilih akun dari COA aktif tahun fiskal tanggal jurnal, bukan COA aktif tahun lain', function () {
    $coa2025 = makeCoaForYear('2025', 'active', 'Kas 2025');
    $coa2026 = makeCoaForYear('2026', 'active', 'Kas 2026');

    $account2025 = app(ResolveControlAccountAction::class)->execute('11110', '2025-06-15');
    expect($account2025->coa_version_id)->toBe($coa2025->id)
        ->and($account2025->name)->toBe('Kas 2025');

    $account2026 = app(ResolveControlAccountAction::class)->execute('11110', '2026-03-01');
    expect($account2026->coa_version_id)->toBe($coa2026->id)
        ->and($account2026->name)->toBe('Kas 2026');
});

test('resolver memakai satu-satunya COA aktif bila tahun fiskal tanggal jurnal tidak punya COA sendiri', function () {
    $coa2026 = makeCoaForYear('2026', 'active', 'Kas 2026');

    $account = app(ResolveControlAccountAction::class)->execute('11110', '2025-06-15');

    expect($account->coa_version_id)->toBe($coa2026->id);
});

test('resolver menolak 422 bila tidak ada COA aktif sama sekali', function () {
    makeCoaForYear('2026', 'archived', 'Kas 2026');

    expect(fn () => app(ResolveControlAccountAction::class)->execute('11110', '2025-06-15'))
        ->toThrow(ValidationException::class);
});

test('resolver menolak 422 bila ada dua COA aktif untuk tahun fiskal yang sama', function () {
    $fiscalYear = FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);

    foreach (['A', 'B'] as $suffix) {
        $coa = CoaVersion::factory()->create([
            'fiscal_year_id' => $fiscalYear->id,
            'status' => 'active',
        ]);
        Account::factory()->create([
            'coa_version_id' => $coa->id,
            'code' => '11110',
            'name' => 'Kas ' . $suffix,
            'type' => 'asset',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);
    }

    expect(fn () => app(ResolveControlAccountAction::class)->execute('11110', '2026-03-01'))
        ->toThrow(ValidationException::class);
});

/**
 * COA 2025 berisi Cash (11100) yang dipetakan split ke Cash in Banks (11110) di COA 2026.
 * Mengembalikan [coa2025, coa2026, akun 11100 di COA 2025, akun 11110 di COA 2026].
 */
function makeSplitCoaPair(): array
{
    $coa2025 = makeCoaForYear('2025', 'active', 'Kas 2025');
    Account::where('coa_version_id', $coa2025->id)->where('code', '11110')->delete();
    $cash2025 = Account::factory()->create([
        'coa_version_id' => $coa2025->id,
        'code' => '11100',
        'name' => 'Cash',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'is_active' => true,
    ]);

    $coa2026 = makeCoaForYear('2026', 'active', 'Cash in Banks');
    $bank2026 = Account::where('coa_version_id', $coa2026->id)->where('code', '11110')->firstOrFail();

    AccountMapping::create([
        'source_account_id' => $cash2025->id,
        'target_account_id' => $bank2026->id,
        'type' => 'split',
        'notes' => 'Cash split into Cash in Banks',
    ]);

    return [$coa2025, $coa2026, $cash2025, $bank2026];
}

test('kode kontrol yang tidak ada di COA tahun jurnal diselesaikan lewat mapping terbalik (2025 -> 11100)', function () {
    [$coa2025, , $cash2025] = makeSplitCoaPair();

    $account = app(ResolveControlAccountAction::class)->execute('11110', '2025-06-15');

    expect($account->id)->toBe($cash2025->id)
        ->and($account->coa_version_id)->toBe($coa2025->id)
        ->and($account->code)->toBe('11100');
});

test('kode kontrol yang ada langsung di COA tahun jurnal dipakai tanpa mapping (2026 -> 11110)', function () {
    [, $coa2026, , $bank2026] = makeSplitCoaPair();

    $account = app(ResolveControlAccountAction::class)->execute('11110', '2026-03-01');

    expect($account->id)->toBe($bank2026->id)
        ->and($account->coa_version_id)->toBe($coa2026->id);
});

test('resolver menolak 422 bila kode kontrol tidak punya padanan mapping di COA tahun jurnal', function () {
    $coa2025 = makeCoaForYear('2025', 'active', 'Kas 2025');
    Account::where('coa_version_id', $coa2025->id)->where('code', '11110')->delete();
    makeCoaForYear('2026', 'active', 'Cash in Banks');

    expect(fn () => app(ResolveControlAccountAction::class)->execute('11110', '2025-06-15'))
        ->toThrow(ValidationException::class);
});

test('resolver menolak 422 bila padanan mapping ambigu (dua akun COA tahun jurnal terhubung)', function () {
    [$coa2025, , , $bank2026] = makeSplitCoaPair();

    $cashKecil2025 = Account::factory()->create([
        'coa_version_id' => $coa2025->id,
        'code' => '11105',
        'name' => 'Petty Cash 2025',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'is_active' => true,
    ]);
    AccountMapping::create([
        'source_account_id' => $cashKecil2025->id,
        'target_account_id' => $bank2026->id,
        'type' => 'split',
        'notes' => 'uji ambigu',
    ]);

    expect(fn () => app(ResolveControlAccountAction::class)->execute('11110', '2025-06-15'))
        ->toThrow(ValidationException::class);
});

test('db:seed kedua tidak menambah COA maupun tahun fiskal dan COA buatan pengguna tetap utuh', function () {
    $this->seed(CoaSeeder::class);

    $userCoa = CoaVersion::factory()->create(['status' => 'draft']);
    $userAccount = Account::factory()->create([
        'coa_version_id' => $userCoa->id,
        'code' => '99999',
        'name' => 'Akun pengguna',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'is_active' => true,
    ]);

    $fiscalYears = FiscalYear::count();
    $coaVersions = CoaVersion::count();
    $accounts = Account::count();

    $this->seed(CoaSeeder::class);

    expect(FiscalYear::count())->toBe($fiscalYears)
        ->and(CoaVersion::count())->toBe($coaVersions)
        ->and(Account::count())->toBe($accounts)
        ->and(CoaVersion::where('status', 'active')->count())->toBe(2)
        ->and($userCoa->fresh()->status)->toBe('draft')
        ->and($userAccount->fresh()->name)->toBe('Akun pengguna');
});
