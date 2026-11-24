<?php

use App\Actions\AccountingPosting\ResolveControlAccountAction;
use App\Models\Account;
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

test('resolver menolak 422 bila tahun fiskal tanggal jurnal tidak punya COA aktif', function () {
    makeCoaForYear('2026', 'active', 'Kas 2026');

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
        ->and(CoaVersion::where('status', 'active')->count())->toBe(1)
        ->and($userCoa->fresh()->status)->toBe('draft')
        ->and($userAccount->fresh()->name)->toBe('Akun pengguna');
});
