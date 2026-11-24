<?php

use App\Actions\AccountingPosting\PostAssetAcquisitionJournalAction;
use App\Models\Account;
use App\Models\Asset;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('asset-acquisition-journal-posting');

/**
 * COA aktif dengan akun yang dipakai jurnal perolehan aset, plus fiscal year
 * 2025 (closed) dan 2026 (open).
 */
function makeAssetAcquisitionFixtures(): void
{
    FiscalYear::factory()->create([
        'name' => '2025',
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
        'status' => 'closed',
    ]);

    $openYear = FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);

    // Tanpa fiscal_year_id eksplisit, CoaVersionFactory membuat FY acak (2020-2070, open)
    // yang ikut terpilih sebagai saldo awal dan membuat tes bergantung urutan.
    $coaVersion = CoaVersion::factory()->create([
        'status' => 'active',
        'fiscal_year_id' => $openYear->id,
    ]);

    $accounts = [
        ['11110', 'Cash in Bank', 'asset', 'debit'],
        ['12100', 'Equipment', 'asset', 'debit'],
        ['12200', 'Accumulated Depreciation', 'asset', 'credit'],
        ['32000', 'Retained Earnings', 'equity', 'credit'],
    ];

    foreach ($accounts as [$code, $name, $type, $normal]) {
        Account::factory()->create([
            'coa_version_id' => $coaVersion->id,
            'code' => $code,
            'name' => $name,
            'type' => $type,
            'normal_balance' => $normal,
            'is_active' => true,
        ]);
    }
}

function accountIdByCode(string $code): int
{
    return (int) Account::query()->where('code', $code)->value('id');
}

test('perolehan dalam fiscal year terbuka dijurnal pada tanggal perolehan (Dr 12100 / Cr 11110)', function () {
    makeAssetAcquisitionFixtures();

    $asset = Asset::factory()->create([
        'asset_code' => 'FA-2026-0101',
        'purchase_date' => '2026-03-10',
        'purchase_cost' => 18000000,
        'depreciation_start_date' => '2026-03-10',
        'useful_life_months' => 36,
        'salvage_value' => 0,
        'status' => 'active',
    ]);

    $entry = app(PostAssetAcquisitionJournalAction::class)->execute($asset);

    expect($entry->entry_date->toDateString())->toBe('2026-03-10')
        ->and($entry->status)->toBe('posted');

    $lines = $entry->lines()->get();
    expect($lines)->toHaveCount(2);

    $debit = $lines->firstWhere('account_id', accountIdByCode('12100'));
    $credit = $lines->firstWhere('account_id', accountIdByCode('11110'));

    expect((float) $debit?->debit)->toBe(18000000.0)
        ->and((float) $debit?->credit)->toBe(0.0)
        ->and((float) $credit?->credit)->toBe(18000000.0)
        ->and((float) $credit?->debit)->toBe(0.0);
});

test('perolehan sebelum fiscal year terbuka dijurnal sebagai saldo awal di hari pertama FY terbuka tanpa kas', function () {
    makeAssetAcquisitionFixtures();

    // 36 juta, garis lurus 36 bulan dari 15 Maret 2024.
    // Sampai 1 Januari 2026 berjalan 21 bulan penuh = 21 juta akumulasi, neto 15 juta.
    $asset = Asset::factory()->create([
        'asset_code' => 'FA-2024-0007',
        'purchase_date' => '2024-03-15',
        'purchase_cost' => 36000000,
        'depreciation_start_date' => '2024-03-15',
        'useful_life_months' => 36,
        'salvage_value' => 0,
        'status' => 'active',
    ]);

    $entry = app(PostAssetAcquisitionJournalAction::class)->execute($asset);

    expect($entry->entry_date->toDateString())->toBe('2026-01-01')
        ->and($entry->status)->toBe('posted');

    $lines = $entry->lines()->get();
    expect($lines)->toHaveCount(3)
        ->and($lines->firstWhere('account_id', accountIdByCode('11110')))->toBeNull();

    $equipment = $lines->firstWhere('account_id', accountIdByCode('12100'));
    $accumulated = $lines->firstWhere('account_id', accountIdByCode('12200'));
    $equity = $lines->firstWhere('account_id', accountIdByCode('32000'));

    expect((float) $equipment?->debit)->toBe(36000000.0)
        ->and((float) $accumulated?->credit)->toBe(21000000.0)
        ->and((float) $equity?->credit)->toBe(15000000.0);

    $totalDebit = (float) $lines->sum('debit');
    $totalCredit = (float) $lines->sum('credit');
    expect($totalDebit)->toBe($totalCredit);
});

test('aset disposed sebelum fiscal year terbuka tidak masuk saldo awal (tidak ada jurnal ACQ)', function () {
    makeAssetAcquisitionFixtures();

    // Diperoleh 2024 (FY 2024 tidak ada / bukan open) lalu sudah dihapusbukukan.
    $asset = Asset::factory()->create([
        'asset_code' => 'FA-2024-0099',
        'purchase_date' => '2024-06-01',
        'purchase_cost' => 12000000,
        'depreciation_start_date' => '2024-06-01',
        'useful_life_months' => 24,
        'salvage_value' => 0,
        'status' => 'disposed',
    ]);

    $action = app(PostAssetAcquisitionJournalAction::class);

    expect($action->isPostable($asset))->toBeFalse();
    expect(JournalEntry::query()->where('reference', 'ACQ-FA-2024-0099')->exists())->toBeFalse();
});

test('aset disposed yang diperoleh dalam fiscal year terbuka tetap dijurnal', function () {
    makeAssetAcquisitionFixtures();

    $asset = Asset::factory()->create([
        'asset_code' => 'FA-2026-0150',
        'purchase_date' => '2026-02-01',
        'purchase_cost' => 7000000,
        'depreciation_start_date' => '2026-02-01',
        'useful_life_months' => 24,
        'salvage_value' => 0,
        'status' => 'disposed',
    ]);

    expect(app(PostAssetAcquisitionJournalAction::class)->isPostable($asset))->toBeTrue();
});

test('perolehan yang dijurnal ulang dengan referensi sama dikembalikan tanpa jurnal ganda', function () {
    makeAssetAcquisitionFixtures();

    $asset = Asset::factory()->create([
        'asset_code' => 'FA-2026-0102',
        'purchase_date' => '2026-05-02',
        'purchase_cost' => 5000000,
        'status' => 'active',
    ]);

    $action = app(PostAssetAcquisitionJournalAction::class);
    $first = $action->execute($asset);
    $second = $action->execute($asset);

    expect($second->id)->toBe($first->id)
        ->and(JournalEntry::query()->where('reference', 'ACQ-FA-2026-0102')->count())->toBe(1);
});
