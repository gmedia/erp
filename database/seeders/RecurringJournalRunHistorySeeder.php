<?php

namespace Database\Seeders;

use App\Actions\AccountingPosting\ResolveControlAccountAction;
use App\Actions\RecurringJournals\ExecuteRecurringJournalAction;
use App\Models\Account;
use App\Models\RecurringJournal;
use App\Models\User;
use App\Support\Demo\DemoCalendar;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Riwayat run bulanan 13 bulan untuk jurnal berulang demo (#603 butir 1).
 *
 * Setiap template dijalankan lewat ExecuteRecurringJournalAction (alur aplikasi), bukan
 * menulis jurnal langsung, sehingga nomor jurnal, validasi periode, dan validasi akun
 * mengikuti produksi. Jendela demo dimulai di FY lalu, jadi dua bulan pertama memakai
 * akun COA tahun lalu; bulan berikutnya memakai akun COA aktif.
 *
 * Idempoten: bila run terakhir sudah berada di bulan berjalan, seeder tidak mengulang.
 */
class RecurringJournalRunHistorySeeder extends Seeder
{
    public function run(): void
    {
        $templates = RecurringJournal::query()
            ->where('frequency', 'monthly')
            ->orderBy('id')
            ->get();

        if ($templates->isEmpty()) {
            return;
        }

        $windowStart = DemoCalendar::windowStart();
        $windowEnd = now()->startOfMonth();

        $hasHistory = $templates->every(fn (RecurringJournal $template): bool => $template->last_run_date !== null
            && $template->last_run_date->greaterThanOrEqualTo($windowEnd));

        if ($hasHistory) {
            return;
        }

        $admin = User::query()->where('email', config('app.admin'))->first();

        if ($admin !== null) {
            Auth::setUser($admin);
        }

        $action = app(ExecuteRecurringJournalAction::class);
        $originalNow = Carbon::getTestNow();

        try {
            foreach ($templates as $template) {
                $template->update([
                    'auto_post' => true,
                    'is_active' => true,
                    'next_run_date' => $windowStart->toDateString(),
                    'last_run_date' => null,
                ]);

                // Dua bulan pertama jendela berada di FY lalu: pakai akun COA tahun lalu.
                $this->alignTemplateAccounts($template, $windowStart);

                foreach ([0, 1] as $offset) {
                    Carbon::setTestNow($windowStart->copy()->addMonths($offset)->day(5)->setTime(10, 0));
                    $action->execute($template->fresh());
                }

                // Bulan berikutnya masuk FY berjalan: ganti akun ke COA aktif.
                $this->alignTemplateAccounts($template, $windowStart->copy()->addMonths(2));

                for ($offset = 2; $offset < DemoCalendar::MONTHS; $offset++) {
                    Carbon::setTestNow($windowStart->copy()->addMonths($offset)->day(5)->setTime(10, 0));
                    $action->execute($template->fresh());
                }
            }
        } finally {
            Carbon::setTestNow($originalNow);
        }

        // Jam test dipakai hanya untuk tanggal jurnal. created_at/updated_at jurnal demo
        // tetap waktu seed sebenarnya agar master/config tidak tampak dibuat setelah transaksi.
        $seededAt = now();

        foreach ($templates as $template) {
            $entryIds = DB::table('journal_entries')
                ->where('source_type', RecurringJournal::class)
                ->where('source_id', $template->id)
                ->pluck('id');

            if ($entryIds->isEmpty()) {
                continue;
            }

            DB::table('journal_entries')
                ->whereIn('id', $entryIds)
                ->update(['created_at' => $seededAt, 'updated_at' => $seededAt]);
            DB::table('journal_entry_lines')
                ->whereIn('journal_entry_id', $entryIds)
                ->update(['created_at' => $seededAt, 'updated_at' => $seededAt]);
        }
    }

    /**
     * Samakan akun baris template dengan COA yang berlaku pada tanggal tersebut.
     * Baris debit adalah beban (52000); baris kredit adalah kas/bank (11110 atau 11100).
     */
    private function alignTemplateAccounts(RecurringJournal $template, Carbon $date): void
    {
        $template->load('lines');

        $coaVersionId = app(ResolveControlAccountAction::class)
            ->resolveVersion($date->toDateString())
            ->id;

        $expenseAccountId = Account::query()
            ->where('coa_version_id', $coaVersionId)
            ->where('code', '52000')
            ->where('is_active', true)
            ->value('id');

        $bankAccountId = Account::query()
            ->where('coa_version_id', $coaVersionId)
            ->whereIn('code', ['11110', '11100'])
            ->where('is_active', true)
            ->whereDoesntHave('children')
            ->orderByRaw("FIELD(code, '11110', '11100')")
            ->value('id');

        if ($expenseAccountId === null || $bankAccountId === null) {
            return;
        }

        foreach ($template->lines as $line) {
            $line->update([
                'account_id' => (float) $line->debit > 0 ? $expenseAccountId : $bankAccountId,
            ]);
        }
    }
}
