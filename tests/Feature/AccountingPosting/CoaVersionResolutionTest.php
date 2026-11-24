<?php

use App\Actions\AccountingPosting\ResolveControlAccountAction;
use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class)->group('accounting-posting');

beforeEach(function () {
    Sanctum::actingAs(createTestUserWithPermissions(['journal_entry', 'journal_entry.edit']), ['*']);
});

function coaResFiscalYear(string $name): FiscalYear
{
    return FiscalYear::factory()->create([
        'name' => $name,
        'start_date' => "{$name}-01-01",
        'end_date' => "{$name}-12-31",
        'status' => 'open',
    ]);
}

/** COA aktif dengan akun kas (11110) dan pendapatan (41000). */
function coaResVersion(?FiscalYear $fiscalYear, string $status = 'active'): CoaVersion
{
    $version = CoaVersion::factory()->create([
        'fiscal_year_id' => $fiscalYear?->id,
        'status' => $status,
    ]);

    Account::factory()->create([
        'coa_version_id' => $version->id,
        'code' => '11110',
        'name' => 'Cash',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'is_active' => true,
    ]);
    Account::factory()->create([
        'coa_version_id' => $version->id,
        'code' => '41000',
        'name' => 'Revenue',
        'type' => 'revenue',
        'normal_balance' => 'credit',
        'is_active' => true,
    ]);

    return $version;
}

function coaResAccountId(CoaVersion $version, string $code): int
{
    return Account::query()->where('coa_version_id', $version->id)->where('code', $code)->firstOrFail()->id;
}

/** @param  array<int, int>  $accountIds  [kas, pendapatan] */
function coaResPostJournal(string $entryDate, array $accountIds): JournalEntry
{
    return app(CreateJournalEntryAction::class)->execute([
        'entry_date' => $entryDate,
        'description' => 'COA resolution fixture',
        'status' => 'posted',
        'journal_type' => 'general',
        'lines' => [
            ['account_id' => $accountIds[0], 'debit' => 1000, 'credit' => 0],
            ['account_id' => $accountIds[1], 'debit' => 0, 'credit' => 1000],
        ],
    ]);
}

test('satu COA aktif untuk tiga tahun fiskal tetap bisa posting (fallback: satu COA aktif di sistem)', function () {
    $fy2024 = coaResFiscalYear('2024');
    coaResFiscalYear('2025');
    coaResFiscalYear('2026');
    $version = coaResVersion(null);

    $cash = coaResAccountId($version, '11110');
    $revenue = coaResAccountId($version, '41000');

    foreach (['2024-03-01', '2025-03-01', '2026-03-01'] as $date) {
        $entry = coaResPostJournal($date, [$cash, $revenue]);

        expect($entry->status)->toBe('posted')
            ->and($entry->lines()->count())->toBe(2);
    }

    expect(app(ResolveControlAccountAction::class)->execute('11110', '2024-03-01')->coa_version_id)
        ->toBe($version->id)
        ->and($fy2024->id)->toBeInt();
});

test('dua COA aktif beda tahun: tanggal jurnal memakai COA tahunnya', function () {
    $fy2025 = coaResFiscalYear('2025');
    $fy2026 = coaResFiscalYear('2026');
    $coa2025 = coaResVersion($fy2025);
    $coa2026 = coaResVersion($fy2026);

    expect(app(ResolveControlAccountAction::class)->execute('11110', '2026-03-01')->coa_version_id)
        ->toBe($coa2026->id)
        ->and(app(ResolveControlAccountAction::class)->execute('11110', '2025-03-01')->coa_version_id)
        ->toBe($coa2025->id);

    $entry = coaResPostJournal('2026-03-01', [
        coaResAccountId($coa2026, '11110'),
        coaResAccountId($coa2026, '41000'),
    ]);

    expect($entry->status)->toBe('posted');
});

test('dua COA aktif tahun sama ditolak 422', function () {
    $fy2026 = coaResFiscalYear('2026');
    coaResVersion($fy2026);
    coaResVersion($fy2026);

    app(ResolveControlAccountAction::class)->execute('11110', '2026-03-01');
})->throws(ValidationException::class);

test('dua COA aktif tahun sama: posting ditolak 422 dan tidak menyimpan jurnal', function () {
    $fy2026 = coaResFiscalYear('2026');
    $versionA = coaResVersion($fy2026);
    coaResVersion($fy2026);

    $before = JournalEntry::count();

    expect(fn () => coaResPostJournal('2026-03-01', [
        coaResAccountId($versionA, '11110'),
        coaResAccountId($versionA, '41000'),
    ]))->toThrow(ValidationException::class);

    expect(JournalEntry::count())->toBe($before);
});

test('baris jurnal dari COA lain ditolak 422', function () {
    $fy2025 = coaResFiscalYear('2025');
    $fy2026 = coaResFiscalYear('2026');
    $coa2025 = coaResVersion($fy2025);
    $coa2026 = coaResVersion($fy2026);

    // Kas dari COA 2026 (benar), pendapatan dari COA 2025 (salah).
    expect(fn () => coaResPostJournal('2026-03-01', [
        coaResAccountId($coa2026, '11110'),
        coaResAccountId($coa2025, '41000'),
    ]))->toThrow(ValidationException::class);
});
