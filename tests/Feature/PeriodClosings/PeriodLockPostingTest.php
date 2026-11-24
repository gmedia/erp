<?php

use App\Actions\AccountingPosting\ReverseDocumentJournalAction;
use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Actions\JournalEntries\PostJournalAction;
use App\Actions\JournalEntries\UpdateJournalEntryAction;
use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\CustomerInvoice;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\PeriodClosing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class)->group('period-closings');

/**
 * Jurnal bertanggal di periode yang sudah ditutup ditolak (#423).
 * monthly mengunci bulannya; annual mengunci seluruh tahun fiskal.
 * Status reopened/draft tidak mengunci.
 */
function periodLockLines(int $accountA, int $accountB): array
{
    return [
        ['account_id' => $accountA, 'debit' => 100, 'credit' => 0, 'memo' => 'd'],
        ['account_id' => $accountB, 'debit' => 0, 'credit' => 100, 'memo' => 'c'],
    ];
}

function periodLockAccounts(): array
{
    $fiscalYear = FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);
    $coa = CoaVersion::factory()->create(['status' => 'active']);
    $accountA = Account::factory()->create(['coa_version_id' => $coa->id, 'is_active' => true]);
    $accountB = Account::factory()->create(['coa_version_id' => $coa->id, 'is_active' => true]);

    return [$fiscalYear, $accountA, $accountB];
}

function postEntryOn(string $date, int $accountA, int $accountB): JournalEntry
{
    return app(CreateJournalEntryAction::class)->execute([
        'entry_date' => $date,
        'description' => 'period lock test',
        'status' => 'posted',
        'lines' => periodLockLines($accountA, $accountB),
    ]);
}

test('jurnal bertanggal di bulan monthly yang ditutup ditolak 422', function () {
    [$fiscalYear, $a, $b] = periodLockAccounts();
    PeriodClosing::factory()->closed()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'closing_type' => 'monthly',
        'period_year' => 2026,
        'period_month' => 3,
    ]);

    expect(fn () => postEntryOn('2026-03-15', $a->id, $b->id))
        ->toThrow(ValidationException::class);
    expect(JournalEntry::where('entry_date', '2026-03-15')->count())->toBe(0);
});

test('jurnal di bulan lain tidak terkunci oleh monthly closing', function () {
    [$fiscalYear, $a, $b] = periodLockAccounts();
    PeriodClosing::factory()->closed()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'closing_type' => 'monthly',
        'period_year' => 2026,
        'period_month' => 3,
    ]);

    expect(postEntryOn('2026-04-15', $a->id, $b->id))->toBeInstanceOf(JournalEntry::class);
});

test('jurnal bertanggal di tahun fiskal yang ditutup annual ditolak 422', function () {
    [$fiscalYear, $a, $b] = periodLockAccounts();
    PeriodClosing::factory()->annual()->closed()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'period_year' => 2026,
        'period_month' => null,
    ]);

    expect(fn () => postEntryOn('2026-07-01', $a->id, $b->id))
        ->toThrow(ValidationException::class);
});

test('annual closing mengunci lewat fiscal_year_id walau tahun fiskal tidak sama dengan tahun kalender', function () {
    // Tahun fiskal Jul 2026 - Jun 2027. period_year annual bisa berbeda dari tahun kalender.
    $fiscalYear = FiscalYear::factory()->create([
        'name' => 'FY 2026/2027',
        'start_date' => '2026-07-01',
        'end_date' => '2027-06-30',
    ]);
    $coa = CoaVersion::factory()->create(['status' => 'active']);
    $a = Account::factory()->create(['coa_version_id' => $coa->id, 'is_active' => true]);
    $b = Account::factory()->create(['coa_version_id' => $coa->id, 'is_active' => true]);
    PeriodClosing::factory()->annual()->closed()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'period_year' => 2026,
        'period_month' => null,
    ]);

    expect(fn () => postEntryOn('2027-02-10', $a->id, $b->id))
        ->toThrow(ValidationException::class);
    expect(fn () => postEntryOn('2026-08-10', $a->id, $b->id))
        ->toThrow(ValidationException::class);
});

test('status reopened dan draft tidak mengunci periode', function () {
    [$fiscalYear, $a, $b] = periodLockAccounts();
    PeriodClosing::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'closing_type' => 'monthly',
        'period_year' => 2026,
        'period_month' => 5,
        'status' => 'reopened',
    ]);
    PeriodClosing::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'closing_type' => 'monthly',
        'period_year' => 2026,
        'period_month' => 6,
        'status' => 'draft',
    ]);

    expect(postEntryOn('2026-05-10', $a->id, $b->id))->toBeInstanceOf(JournalEntry::class);
    expect(postEntryOn('2026-06-10', $a->id, $b->id))->toBeInstanceOf(JournalEntry::class);
});

test('draft yang dipost lewat PostJournalAction di periode tertutup ditolak', function () {
    [$fiscalYear, $a, $b] = periodLockAccounts();
    $draft = app(CreateJournalEntryAction::class)->execute([
        'entry_date' => '2026-02-10',
        'description' => 'draft',
        'status' => 'draft',
        'lines' => periodLockLines($a->id, $b->id),
    ]);
    PeriodClosing::factory()->closed()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'closing_type' => 'monthly',
        'period_year' => 2026,
        'period_month' => 2,
    ]);

    $result = app(PostJournalAction::class)->execute([$draft->id]);

    expect($result['success'])->toBe(0);
    expect($draft->refresh()->status)->toBe('draft');
});

test('pembatalan dokumen ditolak 422 bila tanggal pembalik (hari ini) di periode tertutup', function () {
    [$fiscalYear, $a, $b] = periodLockAccounts();
    $original = postEntryOn('2026-01-05', $a->id, $b->id);
    $document = CustomerInvoice::factory()->create([
        'journal_entry_id' => $original->id,
    ]);

    // Pembalik memakai tanggal hari ini. Periode hari ini ditutup, jadi pembalik ditolak.
    PeriodClosing::factory()->closed()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'closing_type' => 'monthly',
        'period_year' => (int) now()->format('Y'),
        'period_month' => (int) now()->format('n'),
    ]);

    expect(fn () => app(ReverseDocumentJournalAction::class)
        ->execute($document, 'REV-1', 'Invoice'))
        ->toThrow(ValidationException::class);
    expect(JournalEntry::where('reference', 'REV-1')->count())->toBe(0);
});

test('ubah tanggal draft ke periode tertutup ditolak 422', function () {
    [$fiscalYear, $a, $b] = periodLockAccounts();
    $draft = app(CreateJournalEntryAction::class)->execute([
        'entry_date' => '2026-08-10',
        'description' => 'draft pindah tanggal',
        'status' => 'draft',
        'lines' => periodLockLines($a->id, $b->id),
    ]);
    PeriodClosing::factory()->closed()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'closing_type' => 'monthly',
        'period_year' => 2026,
        'period_month' => 9,
    ]);

    expect(fn () => app(UpdateJournalEntryAction::class)->execute($draft, [
        'entry_date' => '2026-09-05',
        'description' => 'draft pindah tanggal',
    ]))->toThrow(ValidationException::class);
    expect($draft->refresh()->entry_date->format('Y-m-d'))->toBe('2026-08-10');
});

test('ubah draft dari tanggal di periode tertutup ke periode terbuka ditolak 422', function () {
    [$fiscalYear, $a, $b] = periodLockAccounts();
    $draft = app(CreateJournalEntryAction::class)->execute([
        'entry_date' => '2026-10-10',
        'description' => 'draft keluar dari periode tertutup',
        'status' => 'draft',
        'lines' => periodLockLines($a->id, $b->id),
    ]);
    PeriodClosing::factory()->closed()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'closing_type' => 'monthly',
        'period_year' => 2026,
        'period_month' => 10,
    ]);

    expect(fn () => app(UpdateJournalEntryAction::class)->execute($draft, [
        'entry_date' => '2026-11-05',
        'description' => 'draft keluar dari periode tertutup',
    ]))->toThrow(ValidationException::class);
    expect($draft->refresh()->entry_date->format('Y-m-d'))->toBe('2026-10-10');
});
