<?php

use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\Department;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('department-dimension');

/**
 * Berkas migrasi pembersihan departemen (#563).
 */
function deptClearMigrationPath(): string
{
    return database_path('migrations/2026_11_27_100000_clear_department_id_on_non_revenue_expense_journal_lines.php');
}

/**
 * Jalankan migrasi pembersihan departemen (#563).
 */
function deptClearMigrationUp(): void
{
    $migration = require deptClearMigrationPath();
    $migration->up();
}

/**
 * Akun aktif untuk satu tipe tertentu pada COA aktif.
 */
function deptClearAccount(int $coaVersionId, string $code, string $type): Account
{
    return Account::factory()->create([
        'coa_version_id' => $coaVersionId,
        'code' => $code,
        'name' => 'Akun ' . $code,
        'type' => $type,
        'normal_balance' => in_array($type, ['asset', 'expense'], true) ? 'debit' : 'credit',
        'is_active' => true,
    ]);
}

/**
 * Satu baris jurnal langsung di tabel (melewati action posting), seolah data lama.
 */
function deptClearLine(
    JournalEntry $entry,
    Account $account,
    ?int $departmentId,
    float $debit,
    float $credit,
): JournalEntryLine {
    return JournalEntryLine::query()->create([
        'journal_entry_id' => $entry->id,
        'account_id' => $account->id,
        'department_id' => $departmentId,
        'debit' => $debit,
        'credit' => $credit,
        'memo' => 'Data lama',
    ]);
}

/**
 * Fixture satu COA aktif, satu departemen, satu jurnal, dan satu akun per tipe.
 *
 * @return array{department: Department, entry: JournalEntry, asset: Account,
 *     liability: Account, equity: Account, revenue: Account, expense: Account}
 */
function deptClearFixture(): array
{
    /** @var CoaVersion $coa */
    $coa = CoaVersion::factory()->create(['status' => 'active']);
    $coaVersionId = (int) $coa->getKey();

    return [
        'department' => Department::factory()->create(),
        'entry' => JournalEntry::factory()->create(),
        'asset' => deptClearAccount($coaVersionId, '11120', 'asset'),
        'liability' => deptClearAccount($coaVersionId, '21100', 'liability'),
        'equity' => deptClearAccount($coaVersionId, '31000', 'equity'),
        'revenue' => deptClearAccount($coaVersionId, '41000', 'revenue'),
        'expense' => deptClearAccount($coaVersionId, '51100', 'expense'),
    ];
}

it('mengosongkan department_id hanya pada baris akun non pendapatan/beban', function () {
    $f = deptClearFixture();
    $deptId = $f['department']->id;

    $assetLine = deptClearLine($f['entry'], $f['asset'], $deptId, 1500000, 0);
    $liabilityLine = deptClearLine($f['entry'], $f['liability'], $deptId, 0, 1500000);
    $equityLine = deptClearLine($f['entry'], $f['equity'], $deptId, 0, 200000);
    $revenueLine = deptClearLine($f['entry'], $f['revenue'], $deptId, 0, 900000);
    $expenseLine = deptClearLine($f['entry'], $f['expense'], $deptId, 300000, 0);
    $untaggedAsset = deptClearLine($f['entry'], $f['asset'], null, 50000, 0);

    deptClearMigrationUp();

    expect($assetLine->fresh()->department_id)->toBeNull()
        ->and($liabilityLine->fresh()->department_id)->toBeNull()
        ->and($equityLine->fresh()->department_id)->toBeNull()
        ->and($revenueLine->fresh()->department_id)->toBe($deptId)
        ->and($expenseLine->fresh()->department_id)->toBe($deptId)
        ->and($untaggedAsset->fresh()->department_id)->toBeNull();
});

it('tidak mengubah nominal debit dan kredit', function () {
    $f = deptClearFixture();
    $deptId = $f['department']->id;

    $assetLine = deptClearLine($f['entry'], $f['asset'], $deptId, 1500000, 0);
    $revenueLine = deptClearLine($f['entry'], $f['revenue'], $deptId, 0, 900000);

    deptClearMigrationUp();

    expect((float) $assetLine->fresh()->debit)->toBe(1500000.0)
        ->and((float) $assetLine->fresh()->credit)->toBe(0.0)
        ->and((float) $revenueLine->fresh()->credit)->toBe(900000.0);
});

it('idempoten bila dijalankan dua kali', function () {
    $f = deptClearFixture();
    $deptId = $f['department']->id;

    $assetLine = deptClearLine($f['entry'], $f['asset'], $deptId, 1500000, 0);
    $expenseLine = deptClearLine($f['entry'], $f['expense'], $deptId, 300000, 0);

    deptClearMigrationUp();
    $afterFirst = JournalEntryLine::query()->orderBy('id')->get(['id', 'department_id', 'debit', 'credit'])->toArray();

    deptClearMigrationUp();
    $afterSecond = JournalEntryLine::query()->orderBy('id')->get(['id', 'department_id', 'debit', 'credit'])->toArray();

    expect($afterSecond)->toBe($afterFirst)
        ->and($assetLine->fresh()->department_id)->toBeNull()
        ->and($expenseLine->fresh()->department_id)->toBe($deptId);
});

it('down() tidak memulihkan department_id yang sudah dikosongkan', function () {
    $f = deptClearFixture();

    $assetLine = deptClearLine($f['entry'], $f['asset'], $f['department']->id, 1500000, 0);

    deptClearMigrationUp();

    $migration = require deptClearMigrationPath();
    $migration->down();

    expect($assetLine->fresh()->department_id)->toBeNull();
});
