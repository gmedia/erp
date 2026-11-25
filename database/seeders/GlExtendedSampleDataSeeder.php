<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\BankReconciliation;
use App\Models\FiscalYear;
use App\Models\PeriodClosing;
use App\Models\RecurringJournal;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GlExtendedSampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->first() ?? User::factory()->create();
        $fiscalYear = FiscalYear::where('status', 'open')->first() ?? FiscalYear::factory()->create([
            'name' => 'FY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'open',
        ]);
        $accounts = $this->accounts();

        DB::transaction(function () use ($fiscalYear, $accounts, $user): void {
            $this->seedRecurringJournals($fiscalYear, $accounts, $user);
            $this->seedBankReconciliations($fiscalYear, $accounts, $user);
            $this->seedPeriodClosings($fiscalYear, $accounts, $user);
            $this->seedAccountBalances($fiscalYear, $accounts);
        });
    }

    /**
     * @return Collection<int, Account>
     */
    private function accounts(): Collection
    {
        // Hanya akun daun: akun induk (punya anak) tidak boleh dipakai jurnal (#437).
        $accounts = Account::query()
            ->where('is_active', true)
            ->whereDoesntHave('children')
            ->orderBy('code')
            ->take(5)
            ->get();

        if ($accounts->count() >= 5) {
            return $accounts;
        }

        return $accounts->merge(Account::factory()->count(5 - $accounts->count())->create());
    }

    private function seedRecurringJournals(FiscalYear $fiscalYear, Collection $accounts, User $user): void
    {
        $definitions = [
            ['name' => 'Sewa Kantor Bulanan', 'amount' => 15000000, 'frequency' => 'monthly'],
            ['name' => 'Listrik dan Air', 'amount' => 1250000, 'frequency' => 'monthly'],
            ['name' => 'Internet dan Telepon', 'amount' => 2500000, 'frequency' => 'monthly'],
            ['name' => 'Jasa Kebersihan', 'amount' => 3750000, 'frequency' => 'monthly'],
        ];

        foreach ($definitions as $index => $definition) {
            $name = $definition['name'];

            // Bersihkan nama demo lama (W5-22/W6-13).
            $legacyNames = [
                'Recurring Journal Sample ' . ($index + 1),
                'Jurnal Berulang Beban Operasional ' . ($index + 1),
            ];

            foreach ($legacyNames as $legacyName) {
                $legacyJournals = RecurringJournal::where('name', $legacyName)
                    ->where('fiscal_year_id', $fiscalYear->id)
                    ->get();

                foreach ($legacyJournals as $legacyJournal) {
                    if (RecurringJournal::where('name', $name)->where('fiscal_year_id', $fiscalYear->id)->exists()) {
                        $legacyJournal->lines()->delete();
                        $legacyJournal->delete();
                    } else {
                        $legacyJournal->update(['name' => $name]);

                        break;
                    }
                }
            }

            $journal = RecurringJournal::updateOrCreate(
                ['name' => $name, 'fiscal_year_id' => $fiscalYear->id],
                [
                    'description' => $name . ' - {MM}/{YYYY}',
                    'reference_template' => 'RJ-{YYYY}-{MM}-{DD}-' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                    'frequency' => $definition['frequency'],
                    // Selalu jadwal berikutnya, tidak pernah di masa lalu (W6-13).
                    // Semua jurnal bulanan berbagi satu bulan jadwal (W7-15).
                    'next_run_date' => now()->startOfMonth()->addMonth(),
                    'total_amount' => $definition['amount'],
                    'created_by' => $user->id,
                ],
            );

            $journal->lines()->delete();
            $journal->lines()->createMany([
                ['account_id' => $accounts[0]->id, 'debit' => $definition['amount'], 'credit' => 0, 'memo' => 'Beban operasional bulanan'],
                ['account_id' => $accounts[1]->id, 'debit' => 0, 'credit' => $definition['amount'], 'memo' => 'Kas keluar untuk beban operasional'],
            ]);
        }
    }

    private function seedBankReconciliations(FiscalYear $fiscalYear, Collection $accounts, User $user): void
    {
        // Akun rekonsiliasi harus akun bank leaf, bukan akun induk (W5-21).
        $bankAccount = Account::query()
            ->where('code', '11110')
            ->first() ?? $accounts[0];

        // Bulan lalu sudah berakhir: Completed dengan selisih 0 (W7-15).
        // Bulan berjalan belum berakhir dan belum selesai: In Progress dengan selisih nyata.
        $periods = [
            [
                'status' => 'in_progress',
                'period' => now()->startOfMonth(),
                'balance' => 10500000,
                'difference' => 25000,
                'completedAt' => null,
            ],
            [
                'status' => 'completed',
                'period' => now()->startOfMonth()->subMonth(),
                'balance' => 10000000,
                'difference' => 0,
                'completedAt' => now()->startOfMonth()->subMonth()->endOfMonth()->addDays(2),
            ],
        ];

        foreach ($periods as $period) {
            $periodStart = $period['period'];
            $periodEnd = $periodStart->copy()->endOfMonth();

            $reconciliation = BankReconciliation::updateOrCreate(
                [
                    'account_id' => $bankAccount->id,
                    'period_start' => $periodStart->toDateString(),
                    'period_end' => $periodEnd->toDateString(),
                ],
                [
                    'fiscal_year_id' => $fiscalYear->id,
                    'reconciliation_date' => $periodEnd->toDateString(),
                    'statement_balance' => $period['balance'],
                    'book_balance' => $period['balance'] - $period['difference'],
                    'reconciled_balance' => $period['balance'] - $period['difference'],
                    'difference' => $period['difference'],
                    'status' => $period['status'],
                    'completed_by' => $period['completedAt'] ? $user->id : null,
                    'completed_at' => $period['completedAt'],
                    'created_by' => $user->id,
                ],
            );

            $reconciliation->items()->delete();
            $reconciliation->items()->createMany([
                [
                    'transaction_date' => $periodStart->toDateString(),
                    'description' => 'Biaya administrasi bank',
                    'debit' => 0,
                    'credit' => 25000,
                    'type' => 'bank_fee',
                    'is_reconciled' => true,
                ],
                [
                    'transaction_date' => $periodEnd->toDateString(),
                    'description' => 'Pendapatan bunga bank',
                    'debit' => 25000,
                    'credit' => 0,
                    'type' => 'interest',
                    'is_reconciled' => $period['status'] === 'completed',
                ],
            ]);
        }
    }

    private function seedPeriodClosings(FiscalYear $fiscalYear, Collection $accounts, User $user): void
    {
        $periodYear = (int) $fiscalYear->start_date->format('Y');

        foreach ([1, 2] as $month) {
            PeriodClosing::updateOrCreate(
                [
                    'fiscal_year_id' => $fiscalYear->id,
                    'period_month' => $month,
                    'period_year' => $periodYear,
                    'closing_type' => 'monthly',
                ],
                [
                    'status' => 'closed',
                    'net_income' => $this->monthlyNetIncome(
                        $fiscalYear,
                        $month,
                        $periodYear,
                    ),
                    'retained_earnings_account_id' => $accounts[2]->id,
                    'closed_by' => $user->id,
                    // Ditutup pada awal bulan berikutnya, bukan tanggal seed (W7-15).
                    'closed_at' => Carbon::create($periodYear, $month, 1)->addMonth()->startOfDay(),
                    'created_by' => $user->id,
                ],
            );
        }

        PeriodClosing::updateOrCreate(
            [
                'fiscal_year_id' => $fiscalYear->id,
                'period_month' => 3,
                'period_year' => $periodYear,
                'closing_type' => 'monthly',
            ],
            [
                'status' => 'draft',
                'retained_earnings_account_id' => $accounts[2]->id,
                'created_by' => $user->id,
            ],
        );
    }

    /**
     * Laba bersih bulanan dari jurnal posted (revenue - expense), agar
     * period closing hasil seed tidak menampilkan Rp 0 (W5-16).
     */
    private function monthlyNetIncome(
        FiscalYear $fiscalYear,
        int $month,
        int $year,
    ): float {
        $row = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
            ->where('journal_entries.fiscal_year_id', $fiscalYear->id)
            ->where('journal_entries.status', 'posted')
            ->where('journal_entries.journal_type', '<>', 'closing')
            ->whereIn('accounts.type', ['revenue', 'expense'])
            ->whereYear('journal_entries.entry_date', $year)
            ->whereMonth('journal_entries.entry_date', $month)
            // revenue normal kredit, expense normal debit → credit - debit
            // sudah merupakan laba bersih.
            ->selectRaw(
                'COALESCE(SUM(journal_entry_lines.credit - journal_entry_lines.debit), 0) as net',
            )
            ->first();

        return round((float) ($row->net ?? 0), 2);
    }

    private function seedAccountBalances(FiscalYear $fiscalYear, Collection $accounts): void
    {
        $periodYear = (int) $fiscalYear->start_date->format('Y');

        foreach ($accounts->take(4) as $account) {
            foreach ([1, 2, 3] as $month) {
                AccountBalance::updateOrCreate(
                    [
                        'account_id' => $account->id,
                        'fiscal_year_id' => $fiscalYear->id,
                        'period_month' => $month,
                        'period_year' => $periodYear,
                    ],
                    [
                        'opening_balance' => 1000000 * $month,
                        'debit_total' => 250000 * $month,
                        'credit_total' => 125000 * $month,
                        'movement' => 125000 * $month,
                        'closing_balance' => (1000000 * $month) + (125000 * $month),
                        'last_recalculated_at' => now(),
                    ],
                );
            }
        }
    }
}
