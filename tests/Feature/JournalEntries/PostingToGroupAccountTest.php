<?php

namespace Tests\Feature\JournalEntries;

use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Actions\JournalEntries\PostJournalAction;
use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
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
