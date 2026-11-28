<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\FiscalYear;
use App\Models\User;
use App\Support\Demo\DemoCalendar;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Anggaran tahun berjalan dengan baris per akun/periode (#603 butir 2).
 *
 * Baris anggaran disusun agar laporan variance punya tiga status bermakna:
 * within_budget (realisasi < 80%), warning (80-100%), dan over_budget (>100%).
 * Alokasi ditentukan dari realisasi periode yang sudah terposting dengan rasio
 * tetap, sehingga status tetap konsisten saat nilai transaksi demo berubah.
 */
class BudgetSampleDataSeeder extends Seeder
{
    /**
     * Rasio realisasi terhadap alokasi per akun/periode.
     *
     * Tiap akun memakai rasio berbeda agar alokasi tidak tampak sebagai pembagian
     * balik dari realisasi (persis 50%/90%/120%). Nilai tetap menghasilkan status
     * within_budget (< 0,8), warning (0,8–1,0), dan over_budget (> 1,0).
     *
     * @var array<int, list<float>>
     */
    private const VARIANCE_RATIOS = [
        [0.62, 0.88, 1.15],
        [0.55, 0.92, 1.22],
        [0.71, 0.85, 1.08],
    ];

    /** Pembulatan alokasi anggaran agar terlihat seperti rencana, bukan hasil hitung mundur. */
    private const ALLOCATION_ROUNDING = 500000.0;

    public function run(): void
    {
        foreach ([
            'Sample draft operational budget for testing Budget module.' => 'Anggaran operasional tahunan (rencana awal)',
            'Sample approved operational budget for testing Budget module.' => 'Anggaran operasional triwulan I yang telah disetujui',
            'Sample locked capital budget for testing Budget module.' => 'Anggaran belanja modal tahunan',
        ] as $legacy => $current) {
            Budget::where('description', $legacy)->update(['description' => $current]);
        }

        $adminUserId = User::query()->where('email', config('app.admin'))->value('id')
            ?? User::query()->value('id');
        $fiscalYear = DemoCalendar::currentFiscalYear();

        if (! $adminUserId || ! $fiscalYear) {
            return;
        }

        $this->seedOperationalBudget($adminUserId, $fiscalYear);
        $this->seedQuarterlyBudget($adminUserId, $fiscalYear);
        $this->seedCapitalBudget($adminUserId, $fiscalYear);
    }

    private function seedOperationalBudget(int $adminUserId, FiscalYear $fiscalYear): void
    {
        $accounts = $this->accountsByCodes(['51000', '52000', '53000']);
        $periods = $this->firstMonths($fiscalYear, count(self::VARIANCE_RATIOS[0]));
        $lines = [];

        foreach ($accounts as $accountIndex => $account) {
            $ratios = self::VARIANCE_RATIOS[$accountIndex % count(self::VARIANCE_RATIOS)];

            foreach ($periods as $index => $period) {
                $actual = $this->actualFor($account, $period['start'], $period['end'], $fiscalYear);

                if ($actual <= 0) {
                    continue;
                }

                $ratio = $ratios[$index % count($ratios)];

                $lines[] = [
                    'account_id' => $account->id,
                    'period_start' => $period['start']->toDateString(),
                    'period_end' => $period['end']->toDateString(),
                    'allocated_amount' => $this->allocatedAmount($actual, $ratio),
                    'notes' => 'Alokasi anggaran per periode',
                ];
            }
        }

        $this->saveBudget(
            $adminUserId,
            $fiscalYear,
            'Anggaran Operasional ' . $fiscalYear->name,
            'Anggaran operasional tahunan (rencana awal)',
            'operational',
            'draft',
            false,
            $lines,
        );
    }

    private function seedQuarterlyBudget(int $adminUserId, FiscalYear $fiscalYear): void
    {
        $accounts = $this->accountsByCodes(['52000']);
        $periods = $this->firstMonths($fiscalYear, 3);
        $lines = [];

        foreach ($accounts as $account) {
            foreach ($periods as $period) {
                $actual = $this->actualFor($account, $period['start'], $period['end'], $fiscalYear);

                if ($actual <= 0) {
                    continue;
                }

                $lines[] = [
                    'account_id' => $account->id,
                    'period_start' => $period['start']->toDateString(),
                    'period_end' => $period['end']->toDateString(),
                    'allocated_amount' => round(($actual * 1.3) / self::ALLOCATION_ROUNDING) * self::ALLOCATION_ROUNDING,
                    'notes' => 'Alokasi anggaran triwulan I',
                ];
            }
        }

        $this->saveBudget(
            $adminUserId,
            $fiscalYear,
            'Anggaran Operasional Triwulan I ' . $fiscalYear->name,
            'Anggaran operasional triwulan I yang telah disetujui',
            'operational',
            'approved',
            true,
            $lines,
        );
    }

    private function seedCapitalBudget(int $adminUserId, FiscalYear $fiscalYear): void
    {
        $accounts = $this->accountsByCodes(['12100']);
        $periods = $this->firstMonths($fiscalYear, 2);

        if ($accounts->isEmpty() || $periods === []) {
            return;
        }

        $account = $accounts->first();
        $lines = [];

        foreach ($periods as $index => $period) {
            $lines[] = [
                'account_id' => $account->id,
                'period_start' => $period['start']->toDateString(),
                'period_end' => $period['end']->toDateString(),
                'allocated_amount' => 150000000.0 + ($index * 50000000),
                'notes' => 'Alokasi belanja modal per periode',
            ];
        }

        $this->saveBudget(
            $adminUserId,
            $fiscalYear,
            'Anggaran Modal ' . $fiscalYear->name,
            'Anggaran belanja modal tahunan',
            'capital',
            'locked',
            true,
            $lines,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    private function saveBudget(
        int $adminUserId,
        FiscalYear $fiscalYear,
        string $name,
        string $description,
        string $budgetType,
        string $status,
        bool $approved,
        array $lines,
    ): void {
        $totalAmount = array_sum(array_column($lines, 'allocated_amount'));

        $budget = Budget::updateOrCreate(
            [
                'fiscal_year_id' => $fiscalYear->id,
                'name' => $name,
            ],
            [
                'ulid' => Str::ulid()->toBase32(),
                'description' => $description,
                'budget_type' => $budgetType,
                'status' => $status,
                'total_amount' => $totalAmount,
                'created_by' => $adminUserId,
                'approved_by' => $approved ? $adminUserId : null,
                'approved_at' => $approved ? now() : null,
            ],
        );

        BudgetLine::query()->where('budget_id', $budget->id)->delete();

        foreach ($lines as $line) {
            BudgetLine::create([
                'budget_id' => $budget->id,
                ...$line,
            ]);
        }
    }

    /**
     * @param  list<string>  $codes
     * @return Collection<int, Account>
     */
    private function accountsByCodes(array $codes): Collection
    {
        $coaVersionId = DemoCalendar::currentCoaVersionId();

        if ($coaVersionId === null) {
            return collect();
        }

        return Account::query()
            ->where('coa_version_id', $coaVersionId)
            ->whereIn('code', $codes)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
    }

    /**
     * Bulan-bulan pertama FY yang sudah berjalan (punya realisasi).
     *
     * @return list<array{start: Carbon, end: Carbon}>
     */
    private function firstMonths(FiscalYear $fiscalYear, int $count): array
    {
        $months = [];
        $cursor = $fiscalYear->start_date->copy()->startOfMonth();
        $lastMonth = now()->startOfMonth()->min($fiscalYear->end_date->copy()->startOfMonth());

        while ($cursor->lessThanOrEqualTo($lastMonth) && count($months) < $count) {
            $months[] = [
                'start' => $cursor->copy()->startOfMonth(),
                'end' => $cursor->copy()->endOfMonth(),
            ];
            $cursor->addMonth();
        }

        return $months;
    }

    private function actualFor(Account $account, Carbon $start, Carbon $end, FiscalYear $fiscalYear): float
    {
        $row = DB::table('journal_entry_lines as l')
            ->join('journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->where('l.account_id', $account->id)
            ->where('e.fiscal_year_id', $fiscalYear->id)
            ->where('e.status', 'posted')
            ->whereBetween('e.entry_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('COALESCE(SUM(l.debit), 0) as debit')
            ->selectRaw('COALESCE(SUM(l.credit), 0) as credit')
            ->first();

        $debit = (float) ($row->debit ?? 0);
        $credit = (float) ($row->credit ?? 0);

        return in_array($account->type, ['expense', 'asset'], true)
            ? $debit - $credit
            : $credit - $debit;
    }

    /**
     * Alokasi anggaran bulat (kelipatan Rp 500.000) dari rasio target, dengan pengaman
     * agar status variance tidak berubah karena pembulatan.
     */
    private function allocatedAmount(float $actual, float $ratio): float
    {
        $rounded = round(($actual / $ratio) / self::ALLOCATION_ROUNDING) * self::ALLOCATION_ROUNDING;

        if ($ratio >= 1.0 && $rounded >= $actual) {
            $rounded = floor(($actual - 1) / self::ALLOCATION_ROUNDING) * self::ALLOCATION_ROUNDING;
        }

        return max(self::ALLOCATION_ROUNDING, $rounded);
    }
}
