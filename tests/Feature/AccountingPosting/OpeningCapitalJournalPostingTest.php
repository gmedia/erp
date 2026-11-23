<?php

use App\Actions\AccountingPosting\PostOpeningCapitalJournalAction;
use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class)->group('opening-capital-journal-posting');

/**
 * @return array{bank: Account, capital: Account}
 */
function makeOpeningCapitalAccounts(): array
{
    $coaVersion = CoaVersion::factory()->create(['status' => 'active']);

    Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '11110',
        'name' => 'Cash in Bank',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'is_active' => true,
    ]);

    Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '31000',
        'name' => 'Paid-in Capital',
        'type' => 'equity',
        'normal_balance' => 'credit',
        'is_active' => true,
    ]);

    // Ambil dari COA aktif saja, sama dengan resolver di action.
    $activeIds = CoaVersion::query()->where('status', 'active')->pluck('id');

    return [
        'bank' => Account::query()->whereIn('coa_version_id', $activeIds)->where('code', '11110')->firstOrFail(),
        'capital' => Account::query()->whereIn('coa_version_id', $activeIds)->where('code', '31000')->firstOrFail(),
    ];
}

test('PostOpeningCapitalJournalAction posts a balanced fixed capital entry from bank to paid-in capital', function () {
    FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);
    $accounts = makeOpeningCapitalAccounts();

    $entry = app(PostOpeningCapitalJournalAction::class)->execute(
        '2026-01-01',
        500000000,
        'OPEN-CAP-2026',
        'Saldo awal modal disetor pemilik',
    );

    expect($entry->status)->toBe('posted')
        ->and($entry->journal_type)->toBe('system');

    /** @var Collection<int, JournalEntryLine> $lines */
    $lines = $entry->lines()->get();
    expect($lines)->toHaveCount(2);

    $bankLine = $lines->firstWhere('account_id', $accounts['bank']->id);
    $capitalLine = $lines->firstWhere('account_id', $accounts['capital']->id);

    expect($bankLine)->not->toBeNull()
        ->and($capitalLine)->not->toBeNull();

    expect((float) $bankLine?->debit)->toBe(500000000.0)
        ->and((float) $bankLine?->credit)->toBe(0.0)
        ->and((float) $capitalLine?->credit)->toBe(500000000.0)
        ->and((float) $capitalLine?->debit)->toBe(0.0);
});

test('PostOpeningCapitalJournalAction is idempotent for the same reference', function () {
    FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);
    makeOpeningCapitalAccounts();

    $action = app(PostOpeningCapitalJournalAction::class);
    $first = $action->execute('2026-01-01', 500000000, 'OPEN-CAP-2026', 'Saldo awal modal');
    $second = $action->execute('2026-01-01', 500000000, 'OPEN-CAP-2026', 'Saldo awal modal');

    expect($second->id)->toBe($first->id)
        ->and(JournalEntry::query()->where('reference', 'OPEN-CAP-2026')->count())->toBe(1);
});

test('PostOpeningCapitalJournalAction memakai akun dari COA aktif, bukan COA nonaktif dengan kode sama', function () {
    FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);

    // COA lama (nonaktif) dengan kode yang sama dan id lebih kecil.
    $oldVersion = CoaVersion::factory()->create(['status' => 'archived']);
    Account::factory()->create([
        'coa_version_id' => $oldVersion->id,
        'code' => '11110',
        'name' => 'Cash in Bank (lama)',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'is_active' => true,
    ]);
    Account::factory()->create([
        'coa_version_id' => $oldVersion->id,
        'code' => '31000',
        'name' => 'Paid-in Capital (lama)',
        'type' => 'equity',
        'normal_balance' => 'credit',
        'is_active' => true,
    ]);

    $accounts = makeOpeningCapitalAccounts();

    $entry = app(PostOpeningCapitalJournalAction::class)->execute(
        '2026-01-01',
        500000000,
        'OPEN-CAP-COA',
        'Saldo awal modal',
    );

    $accountIds = $entry->lines()->pluck('account_id')->sort()->values()->all();
    expect($accountIds)->toBe(collect([$accounts['bank']->id, $accounts['capital']->id])->sort()->values()->all());
});

test('PostOpeningCapitalJournalAction gagal bila akun kontrol tidak ada di COA aktif', function () {
    FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);

    // Hanya akun di COA nonaktif: resolver harus menolak, bukan memakai akun lama.
    $oldVersion = CoaVersion::factory()->create(['status' => 'archived']);
    Account::factory()->create([
        'coa_version_id' => $oldVersion->id,
        'code' => '11110',
        'name' => 'Cash in Bank (lama)',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'is_active' => true,
    ]);

    app(PostOpeningCapitalJournalAction::class)->execute('2026-01-01', 500000000, 'OPEN-CAP-NOCOA', 'Saldo awal');
})->throws(ValidationException::class);

test('PostOpeningCapitalJournalAction rejects a non-positive amount', function () {
    FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);
    makeOpeningCapitalAccounts();

    app(PostOpeningCapitalJournalAction::class)->execute('2026-01-01', 0, 'OPEN-CAP-ZERO', 'Nol');
})->throws(ValidationException::class);
