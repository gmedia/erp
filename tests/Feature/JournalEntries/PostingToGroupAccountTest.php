<?php

namespace Tests\Feature\JournalEntries;

use App\Actions\AccountingPosting\ReverseDocumentJournalAction;
use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Actions\JournalEntries\PostJournalAction;
use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\CustomerInvoice;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class)->group('journal-entries');

beforeEach(function () {
    $this->fiscalYear = FiscalYear::factory()->create([
        'name' => '2026',
        'status' => 'open',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);
    $this->coaVersion = CoaVersion::factory()->create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'status' => 'active',
    ]);

    $this->group = Account::factory()->create([
        'coa_version_id' => $this->coaVersion->id,
        'code' => '11100',
        'is_active' => true,
    ]);
    $this->leaf = Account::factory()->create([
        'coa_version_id' => $this->coaVersion->id,
        'parent_id' => $this->group->id,
        'code' => '11110',
        'is_active' => true,
    ]);
    $this->otherLeaf = Account::factory()->create([
        'coa_version_id' => $this->coaVersion->id,
        'code' => '52000',
        'is_active' => true,
    ]);

    $this->lines = fn (int $debitAccountId): array => [
        ['account_id' => $debitAccountId, 'debit' => 100000, 'credit' => 0],
        ['account_id' => $this->otherLeaf->id, 'debit' => 0, 'credit' => 100000],
    ];
});

test('posted journal to a group account is rejected with localized message', function () {
    expect(fn () => app(CreateJournalEntryAction::class)->execute([
        'entry_date' => '2026-03-02',
        'description' => 'Posting ke akun kelompok',
        'status' => 'posted',
        'lines' => ($this->lines)($this->group->id),
    ]))->toThrow(ValidationException::class);

    expect(JournalEntry::count())->toBe(0);
});

test('draft journal to a group account is rejected too', function () {
    expect(fn () => app(CreateJournalEntryAction::class)->execute([
        'entry_date' => '2026-03-02',
        'description' => 'Draft ke akun kelompok',
        'status' => 'draft',
        'lines' => ($this->lines)($this->group->id),
    ]))->toThrow(ValidationException::class);
});

test('bulk posting a draft that points at a group account is rejected', function () {
    $draft = app(CreateJournalEntryAction::class)->execute([
        'entry_date' => '2026-03-02',
        'description' => 'Draft leaf',
        'status' => 'draft',
        'lines' => ($this->lines)($this->leaf->id),
    ]);

    // Sengaja menyisipkan baris ke akun kelompok lewat DB (instalasi lama).
    $draft->lines()->first()->update(['account_id' => $this->group->id]);

    $result = app(PostJournalAction::class)->execute([$draft->id]);

    expect($result['success'])->toBe(0)
        ->and($result['failures'])->toHaveKey($draft->id)
        ->and($draft->fresh()->status)->toBe('draft');
});

test('posting a journal to leaf accounts still works', function () {
    $entry = app(CreateJournalEntryAction::class)->execute([
        'entry_date' => '2026-03-02',
        'description' => 'Akun daun',
        'status' => 'posted',
        'lines' => ($this->lines)($this->leaf->id),
    ]);

    expect($entry->status)->toBe('posted');
});

/**
 * Menanam saldo posted langsung ke akun kelompok (bypass aturan, seperti instalasi lama).
 */
function seedPostedGroupBalance(
    object $test,
    float $debit,
    float $credit,
    string $date = '2026-02-01',
    ?int $fiscalYearId = null,
): void {
    $entry = JournalEntry::factory()->create([
        'fiscal_year_id' => $fiscalYearId ?? $test->fiscalYear->id,
        'entry_date' => $date,
        'status' => 'posted',
    ]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $entry->id,
        'account_id' => $test->group->id,
        'debit' => $debit,
        'credit' => $credit,
    ]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $entry->id,
        'account_id' => $test->otherLeaf->id,
        'debit' => $credit,
        'credit' => $debit,
    ]);
}

/**
 * Jurnal dua sisi: akun kelompok menerima $groupLines, sisi lawan ke akun daun lain.
 *
 * @param  array<int, array{debit: float, credit: float}>  $groupLines
 */
function groupJournalPayload(object $test, array $groupLines, string $status = 'posted'): array
{
    $lines = [];
    $debit = 0.0;
    $credit = 0.0;
    foreach ($groupLines as $line) {
        $lines[] = ['account_id' => $test->group->id, 'debit' => $line['debit'], 'credit' => $line['credit']];
        $debit += $line['debit'];
        $credit += $line['credit'];
    }
    $lines[] = ['account_id' => $test->otherLeaf->id, 'debit' => $credit, 'credit' => $debit];

    return [
        'entry_date' => '2026-03-02',
        'description' => 'Uji akun kelompok',
        'status' => $status,
        'lines' => $lines,
    ];
}

test('group account with positive balance accepts a credit that reduces it', function () {
    seedPostedGroupBalance($this, 500000, 0);

    $entry = app(CreateJournalEntryAction::class)->execute(
        groupJournalPayload($this, [['debit' => 0, 'credit' => 200000]]),
    );

    expect($entry->status)->toBe('posted');
});

test('group account accepts a reduction that brings its balance to exactly zero', function () {
    seedPostedGroupBalance($this, 500000, 0);

    $entry = app(CreateJournalEntryAction::class)->execute(
        groupJournalPayload($this, [['debit' => 0, 'credit' => 500000]]),
    );

    expect($entry->status)->toBe('posted');
});

test('group account rejects a reduction larger than its balance', function () {
    seedPostedGroupBalance($this, 500000, 0);

    expect(fn () => app(CreateJournalEntryAction::class)->execute(
        groupJournalPayload($this, [['debit' => 0, 'credit' => 500001]]),
    ))->toThrow(ValidationException::class);

    expect(JournalEntry::where('status', 'posted')->count())->toBe(1);
});

test('group account rejects a line that adds to the balance', function () {
    seedPostedGroupBalance($this, 500000, 0);

    expect(fn () => app(CreateJournalEntryAction::class)->execute(
        groupJournalPayload($this, [['debit' => 100000, 'credit' => 0]]),
    ))->toThrow(ValidationException::class);
});

test('group account rejects a reduction that overshoots past zero', function () {
    seedPostedGroupBalance($this, 500000, 0);

    expect(fn () => app(CreateJournalEntryAction::class)->execute(
        groupJournalPayload($this, [['debit' => 0, 'credit' => 600000]]),
    ))->toThrow(ValidationException::class);
});

test('group account with zero balance rejects any line', function () {
    expect(fn () => app(CreateJournalEntryAction::class)->execute(
        groupJournalPayload($this, [['debit' => 0, 'credit' => 100]]),
    ))->toThrow(ValidationException::class);
});

test('lines to the same group account are summed before the check', function () {
    seedPostedGroupBalance($this, 500000, 0);

    // Kredit 300000 + kredit 300000 = N -600000 > B 500000: ditolak walau tiap baris kecil.
    expect(fn () => app(CreateJournalEntryAction::class)->execute(
        groupJournalPayload($this, [
            ['debit' => 0, 'credit' => 300000],
            ['debit' => 0, 'credit' => 300000],
        ]),
    ))->toThrow(ValidationException::class);

    // Kredit 300000 + debit 100000 = N -200000: diterima.
    $entry = app(CreateJournalEntryAction::class)->execute(
        groupJournalPayload($this, [
            ['debit' => 0, 'credit' => 300000],
            ['debit' => 100000, 'credit' => 0],
        ]),
    );

    expect($entry->status)->toBe('posted');
});

test('negative group balance accepts a debit that reduces it', function () {
    seedPostedGroupBalance($this, 0, 500000);

    $entry = app(CreateJournalEntryAction::class)->execute(
        groupJournalPayload($this, [['debit' => 300000, 'credit' => 0]]),
    );

    expect($entry->status)->toBe('posted');
});

test('group balance includes posted journals from every fiscal year', function () {
    $previousYear = FiscalYear::factory()->create([
        'name' => '2025',
        'status' => 'closed',
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
    ]);
    seedPostedGroupBalance($this, 500000, 0, '2025-06-01', $previousYear->id);

    expect(fn () => app(CreateJournalEntryAction::class)->execute(
        groupJournalPayload($this, [['debit' => 0, 'credit' => 600000]]),
    ))->toThrow(ValidationException::class);

    $entry = app(CreateJournalEntryAction::class)->execute(
        groupJournalPayload($this, [['debit' => 0, 'credit' => 500000]]),
    );

    expect($entry->status)->toBe('posted');
});

test('reversing an original journal that posted to a group account is accepted', function () {
    // Jurnal asli dibuat sebelum aturan berlaku (instalasi lama): saldo kelompok jadi nol lalu terbalik.
    $original = JournalEntry::factory()->create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'entry_date' => '2026-02-01',
        'status' => 'posted',
        'source_type' => 'customer_invoice',
        'source_id' => 1,
    ]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $original->id,
        'account_id' => $this->group->id,
        'debit' => 500000,
        'credit' => 0,
    ]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $original->id,
        'account_id' => $this->otherLeaf->id,
        'debit' => 0,
        'credit' => 500000,
    ]);
    // Saldo kelompok sudah nol karena jurnal lain: pembalik (kredit 500000) akan menambah saldo
    // negatif dan hanya lolos lewat allowGroupAccounts.
    seedPostedGroupBalance($this, 0, 500000, '2026-02-10');

    $document = CustomerInvoice::factory()->create([
        'journal_entry_id' => $original->id,
    ]);

    $reversal = app(ReverseDocumentJournalAction::class)->execute(
        $document,
        'INV-TEST-0001',
        'Faktur penjualan',
    );

    expect($reversal)->not->toBeNull()
        ->and($reversal->status)->toBe('posted');

    $groupLine = $reversal->lines()->where('account_id', $this->group->id)->first();
    expect((float) $groupLine->credit)->toBe(500000.0)
        ->and((float) $groupLine->debit)->toBe(0.0);
});

test('payload cannot enable group account bypass for a manual journal', function () {
    seedPostedGroupBalance($this, 500000, 0);

    $payload = groupJournalPayload($this, [['debit' => 0, 'credit' => 600000]]);
    $payload['allow_group_accounts'] = true;
    $payload['allowGroupAccounts'] = true;

    expect(fn () => app(CreateJournalEntryAction::class)->execute($payload))
        ->toThrow(ValidationException::class);
});

test('draft saved under the rule is rechecked when posted', function () {
    seedPostedGroupBalance($this, 500000, 0);

    $draft = app(CreateJournalEntryAction::class)->execute(
        groupJournalPayload($this, [['debit' => 0, 'credit' => 200000]], 'draft'),
    );

    // Saldo berubah setelah draft dibuat: penguranan 200000 tidak lagi memperkecil saldo.
    seedPostedGroupBalance($this, 0, 400000, '2026-02-10');

    $result = app(PostJournalAction::class)->execute([$draft->id]);

    expect($result['success'])->toBe(0)
        ->and($result['failures'])->toHaveKey($draft->id)
        ->and($draft->fresh()->status)->toBe('draft');
});
