<?php

namespace Database\Seeders;

use App\Actions\AccountingPosting\ResolveControlAccountAction;
use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\User;
use App\Support\Demo\DemoCalendar;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Beban operasional variabel bulanan untuk jendela demo 13 bulan (#603 review host).
 *
 * Beban tetap (gaji, sewa, utilitas, internet, kebersihan) berasal dari jurnal berulang.
 * Seeder ini menambahkan komponen variabel yang wajar mengikuti volume penjualan
 * (distribusi, komisi, lembur), sehingga marjin bersih FY berjalan berada di rentang
 * 10-20% dan beban tidak seragam antar bulan. Jurnal dibuat lewat
 * CreateJournalEntryAction agar validasi periode/akun sama dengan produksi.
 *
 * Idempoten: referensi OPEX-VAR-{YYYYMM} dilewati bila sudah ada.
 */
class OperatingExpenseVariableSeeder extends Seeder
{
    /** Porsi beban operasional variabel terhadap pendapatan bulanan. */
    private const REVENUE_RATE = 0.25;

    public function run(): void
    {
        $create = app(CreateJournalEntryAction::class);
        $resolve = app(ResolveControlAccountAction::class);

        $admin = User::query()->where('email', config('app.admin'))->first();

        if ($admin !== null) {
            Auth::setUser($admin);
        }

        foreach (DemoCalendar::months() as $month) {
            $reference = 'OPEX-VAR-' . $month->format('Ym');

            if (JournalEntry::query()->where('reference', $reference)->exists()) {
                continue;
            }

            $fiscalYear = FiscalYear::query()
                ->where('start_date', '<=', $month->toDateString())
                ->where('end_date', '>=', $month->toDateString())
                ->first();

            if ($fiscalYear === null) {
                continue;
            }

            $revenue = $this->monthlyRevenue($fiscalYear, $month);
            $amount = round($revenue * self::REVENUE_RATE, 0);

            if ($amount <= 0) {
                continue;
            }

            $entryDate = $month->copy()->endOfMonth()->toDateString();

            $create->execute([
                'entry_date' => $entryDate,
                'reference' => $reference,
                'description' => 'Beban operasional variabel ' . $month->format('m/Y'),
                'status' => 'posted',
                'journal_type' => 'system',
                'lines' => [
                    [
                        'account_id' => $resolve->execute('52000', $entryDate)->id,
                        'debit' => $amount,
                        'credit' => 0,
                        'memo' => 'Beban distribusi dan komisi penjualan',
                    ],
                    [
                        'account_id' => $this->bankAccountId($resolve, $entryDate),
                        'debit' => 0,
                        'credit' => $amount,
                        'memo' => 'Kas keluar beban operasional variabel',
                    ],
                ],
            ]);
        }
    }

    private function monthlyRevenue(FiscalYear $fiscalYear, Carbon $month): float
    {
        return (float) DB::table('journal_entry_lines as l')
            ->join('journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->join('accounts as a', 'a.id', '=', 'l.account_id')
            ->where('e.status', 'posted')
            ->where('e.fiscal_year_id', $fiscalYear->id)
            ->where('a.type', 'revenue')
            ->whereBetween('e.entry_date', [
                $month->copy()->startOfMonth()->toDateString(),
                $month->copy()->endOfMonth()->toDateString(),
            ])
            ->selectRaw('COALESCE(SUM(l.credit - l.debit), 0) as amount')
            ->value('amount');
    }

    /**
     * Akun bank dari COA yang berlaku. COA tahun lalu hanya punya 11100; COA aktif 11110.
     */
    private function bankAccountId(ResolveControlAccountAction $resolve, string $entryDate): int
    {
        try {
            return $resolve->execute('11110', $entryDate)->id;
        } catch (ValidationException) {
            return $resolve->execute('11100', $entryDate)->id;
        }
    }
}
