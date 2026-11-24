<?php

use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class)->group('ap-journal-posting');

function makeActiveAccountFiscalYear(): FiscalYear
{
    return FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);
}

function postedLines(int $debitAccountId, int $creditAccountId): array
{
    return [
        ['account_id' => $debitAccountId, 'debit' => 1000, 'credit' => 0, 'memo' => 'Debit'],
        ['account_id' => $creditAccountId, 'debit' => 0, 'credit' => 1000, 'memo' => 'Credit'],
    ];
}

test('posting a journal with an account from an archived COA version is rejected with 422', function () {
    makeActiveAccountFiscalYear();

    $activeVersion = CoaVersion::factory()->create(['status' => 'active']);
    $archivedVersion = CoaVersion::factory()->create(['status' => 'archived']);

    $activeAccount = Account::factory()->create([
        'coa_version_id' => $activeVersion->id,
        'code' => '11110',
        'is_active' => true,
    ]);
    $archivedAccount = Account::factory()->create([
        'coa_version_id' => $archivedVersion->id,
        'code' => '52000',
        'is_active' => true,
    ]);

    expect(fn () => app(CreateJournalEntryAction::class)->execute([
        'entry_date' => '2026-03-15',
        'description' => 'Posting ke COA archived',
        'status' => 'posted',
        'lines' => postedLines($archivedAccount->id, $activeAccount->id),
    ]))->toThrow(ValidationException::class);
});

test('posting a journal with an inactive account is rejected with 422', function () {
    makeActiveAccountFiscalYear();

    $activeVersion = CoaVersion::factory()->create(['status' => 'active']);

    $activeAccount = Account::factory()->create([
        'coa_version_id' => $activeVersion->id,
        'code' => '11110',
        'is_active' => true,
    ]);
    $inactiveAccount = Account::factory()->create([
        'coa_version_id' => $activeVersion->id,
        'code' => '52000',
        'is_active' => false,
    ]);

    expect(fn () => app(CreateJournalEntryAction::class)->execute([
        'entry_date' => '2026-03-15',
        'description' => 'Posting ke akun nonaktif',
        'status' => 'posted',
        'lines' => postedLines($inactiveAccount->id, $activeAccount->id),
    ]))->toThrow(ValidationException::class);
});

test('posting a journal with active accounts in the active COA version succeeds', function () {
    makeActiveAccountFiscalYear();

    $activeVersion = CoaVersion::factory()->create(['status' => 'active']);

    $debit = Account::factory()->create([
        'coa_version_id' => $activeVersion->id,
        'code' => '52000',
        'is_active' => true,
    ]);
    $credit = Account::factory()->create([
        'coa_version_id' => $activeVersion->id,
        'code' => '11110',
        'is_active' => true,
    ]);

    $entry = app(CreateJournalEntryAction::class)->execute([
        'entry_date' => '2026-03-15',
        'description' => 'Posting valid',
        'status' => 'posted',
        'lines' => postedLines($debit->id, $credit->id),
    ]);

    expect($entry->status)->toBe('posted');
});
