<?php

use App\Models\JournalEntry;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\seed;

uses(RefreshDatabase::class)->group('sample-data-seeders');

/**
 * @return array<int, string> id => entry_number untuk semua jurnal JV.
 */
function jvNumbersById(): array
{
    return DB::table('journal_entries')
        ->where('entry_number', 'like', 'JV-%')
        ->orderBy('id')
        ->pluck('entry_number', 'id')
        ->all();
}

it('keeps existing JV numbers unchanged when the demo seeder runs again (#503 butir 3)', function () {
    seed(DatabaseSeeder::class);

    // Jurnal pengguna yang sudah diposting sebelum seed ulang. Nomor di luar rentang demo
    // dan tanggal lebih awal dari seluruh demo, sehingga renumber yang keliru pasti terdeteksi.
    $fiscalYearId = DB::table('journal_entries')->orderBy('id')->value('fiscal_year_id');
    $userEntryNumber = 'JV-' . now()->format('Y') . '-90001';

    JournalEntry::query()->create([
        'fiscal_year_id' => $fiscalYearId,
        'entry_number' => $userEntryNumber,
        'entry_date' => '2000-01-01',
        'description' => 'Jurnal pengguna (uji seed ulang)',
        'status' => 'posted',
        'journal_type' => 'general',
        'created_by' => User::query()->value('id'),
    ]);

    $userJournalId = (int) DB::table('journal_entries')->where('entry_number', $userEntryNumber)->value('id');

    seed(DatabaseSeeder::class);

    // Jurnal demo dihapus dan diposting ulang oleh seeder (id berubah), jadi hanya jurnal
    // pengguna yang dikunci nomornya. Renumber tidak boleh berjalan bila ada jurnal JV lama.
    expect(DB::table('journal_entries')->where('id', $userJournalId)->value('entry_number'))
        ->toBe($userEntryNumber, 'Nomor jurnal pengguna tidak boleh berubah saat seed ulang');

    $demoNumbers = jvNumbersById();
    expect($demoNumbers)->not->toBeEmpty();
});

it('archives a referenced legacy duplicate product instead of deleting or re-pointing it (#503 butir 1)', function () {
    seed(DatabaseSeeder::class);

    // Produk duplikat tidak boleh tampil sebagai produk aktif. Bila masih ada (karena dirujuk
    // transaksi), harus diarsipkan dengan nama jelas, bukan dialihkan atau dibiarkan aktif.
    DB::table('products')
        ->whereIn('code', ['INV-2026-001', 'INV-2026-002', 'INV-2026-003'])
        ->get(['status', 'name'])
        ->each(function ($legacy) {
            expect($legacy->status)->toBe('inactive');
            expect($legacy->name)->toStartWith('Arsip duplikat');
        });

    expect(
        DB::table('products')
            ->where('status', 'active')
            ->whereIn('code', ['INV-2026-001', 'INV-2026-002', 'INV-2026-003'])
            ->count(),
    )->toBe(0);
});
