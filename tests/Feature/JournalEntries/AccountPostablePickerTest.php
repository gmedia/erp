<?php

use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class)->group('journal-entries');

beforeEach(function () {
    Sanctum::actingAs(createTestUserWithPermissions(['account']), ['*']);

    $fiscalYear = FiscalYear::factory()->create([
        'name' => '2026',
        'status' => 'open',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);
    $this->coa = CoaVersion::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'active',
    ]);

    $this->group = Account::factory()->create([
        'coa_version_id' => $this->coa->id,
        'code' => '11000',
        'name' => 'Kelompok Kas',
        'is_active' => true,
    ]);
    $this->leaf = Account::factory()->create([
        'coa_version_id' => $this->coa->id,
        'parent_id' => $this->group->id,
        'code' => '11110',
        'name' => 'Kas Besar',
        'is_active' => true,
    ]);
    $this->emptyGroup = Account::factory()->create([
        'coa_version_id' => $this->coa->id,
        'code' => '12000',
        'name' => 'Kelompok Kosong',
        'is_active' => true,
    ]);
    Account::factory()->create([
        'coa_version_id' => $this->coa->id,
        'parent_id' => $this->emptyGroup->id,
        'code' => '12110',
        'is_active' => true,
    ]);
});

/**
 * Menanam saldo posted ke akun kelompok lewat DB (seperti instalasi lama).
 */
function postedBalanceOn(Account $account, Account $counter, float $debit, float $credit): void
{
    $entry = JournalEntry::factory()->create([
        'entry_date' => '2026-02-01',
        'status' => 'posted',
    ]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $entry->id,
        'account_id' => $account->id,
        'debit' => $debit,
        'credit' => $credit,
    ]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $entry->id,
        'account_id' => $counter->id,
        'debit' => $credit,
        'credit' => $debit,
    ]);
}

function pickerCodes(array $query): array
{
    return collect(getJson('/api/accounts?' . http_build_query($query + ['per_page' => 100]))
        ->assertOk()
        ->json('data'))->pluck('code')->sort()->values()->all();
}

test('postable=1 returns leaf accounts', function () {
    expect(pickerCodes(['postable' => 1]))->toContain('11110');
});

test('postable=1 excludes a group account with zero posted balance', function () {
    expect(pickerCodes(['postable' => 1]))->not->toContain('11000');
});

test('postable=1 includes a group account whose posted balance is not zero', function () {
    postedBalanceOn($this->group, $this->emptyGroup, 500000, 0);

    expect(pickerCodes(['postable' => 1]))->toContain('11000');
});

test('postable=1 drops a group account again once its balance returns to zero', function () {
    postedBalanceOn($this->group, $this->emptyGroup, 500000, 0);
    postedBalanceOn($this->group, $this->emptyGroup, 0, 500000);

    expect(pickerCodes(['postable' => 1]))->not->toContain('11000');
});

test('without postable the has_children=0 filter is unchanged', function () {
    postedBalanceOn($this->group, $this->emptyGroup, 500000, 0);

    expect(pickerCodes(['has_children' => 0]))->not->toContain('11000')
        ->and(pickerCodes(['has_children' => 0]))->toContain('11110');
});
