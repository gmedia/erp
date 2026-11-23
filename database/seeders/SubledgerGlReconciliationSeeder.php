<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Menyelaraskan akun GL (neraca) dengan subledger dokumen demo, sehingga
 * Balance Sheet tidak lagi bertentangan dengan AR/AP/aset/stok (W5-21).
 *
 * Pendekatan: posting jurnal "saldo awal" sebesar selisih antara saldo GL
 * saat ini dan target subledger, dengan counterpart Retained Earnings agar
 * laba-rugi tidak terdistorsi. Idempoten: jurnal REC- dihapus lalu dibuat
 * ulang setiap seed.
 */
class SubledgerGlReconciliationSeeder extends Seeder
{
    private const ENTRY_PREFIX = 'REC-';

    public function run(): void
    {
        $fiscalYear = FiscalYear::query()->where('status', 'open')->first();

        if (! $fiscalYear) {
            return;
        }

        $coaVersion = CoaVersion::query()
            ->where('fiscal_year_id', $fiscalYear->id)
            ->orderByRaw("CASE status WHEN 'active' THEN 0 ELSE 1 END")
            ->first();

        if (! $coaVersion) {
            return;
        }

        $accounts = Account::query()
            ->where('coa_version_id', $coaVersion->id)
            ->whereIn('code', ['11200', '11300', '12100', '11120', '21100', '32000'])
            ->pluck('id', 'code');

        if ($accounts->count() < 6) {
            return;
        }

        $this->purgePreviousEntries();

        $targets = [
            // code => [target normal balance, is_debit_normal]
            '11200' => [$this->outstandingReceivable(), true],
            '11300' => [$this->inventoryValue(), true],
            '12100' => [$this->equipmentBookValue(), true],
            '11120' => [2000000.0, true],
            '21100' => [$this->outstandingPayable(), false],
        ];

        $lines = [];
        $retainedAdjustment = 0.0;

        foreach ($targets as $code => [$target, $debitNormal]) {
            $current = $this->postedBalance((int) $accounts[$code], $debitNormal);
            $delta = round($target - $current, 2);

            if (abs($delta) < 0.01) {
                continue;
            }

            if ($debitNormal) {
                $lines[] = $delta > 0
                    ? ['account_id' => $accounts[$code], 'debit' => $delta, 'credit' => 0]
                    : ['account_id' => $accounts[$code], 'debit' => 0, 'credit' => -$delta];
                $retainedAdjustment += $delta;
            } else {
                $lines[] = $delta > 0
                    ? ['account_id' => $accounts[$code], 'debit' => 0, 'credit' => $delta]
                    : ['account_id' => $accounts[$code], 'debit' => -$delta, 'credit' => 0];
                $retainedAdjustment -= $delta;
            }
        }

        if ($lines === [] || abs($retainedAdjustment) < 0.01) {
            return;
        }

        $lines[] = $retainedAdjustment > 0
            ? ['account_id' => $accounts['32000'], 'debit' => 0, 'credit' => $retainedAdjustment]
            : ['account_id' => $accounts['32000'], 'debit' => -$retainedAdjustment, 'credit' => 0];

        $user = User::query()->first();

        $entry = JournalEntry::create([
            'fiscal_year_id' => $fiscalYear->id,
            'entry_number' => self::ENTRY_PREFIX . $fiscalYear->id . '-001',
            'entry_date' => $fiscalYear->start_date->toDateString(),
            'reference' => 'Saldo awal subledger',
            'description' => 'Rekonsiliasi saldo awal GL dengan subledger (AR, AP, persediaan, aset tetap)',
            'status' => 'posted',
            'journal_type' => 'opening',
            'created_by' => $user?->id,
            'posted_by' => $user?->id,
            'posted_at' => now(),
        ]);

        foreach ($lines as $line) {
            $entry->lines()->create($line + ['memo' => 'Saldo awal subledger']);
        }
    }

    private function purgePreviousEntries(): void
    {
        $ids = JournalEntry::query()
            ->where('entry_number', 'like', self::ENTRY_PREFIX . '%')
            ->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        DB::table('journal_entry_lines')->whereIn('journal_entry_id', $ids)->delete();
        JournalEntry::query()->whereIn('id', $ids)->delete();
    }

    private function postedBalance(int $accountId, bool $debitNormal): float
    {
        $row = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entries.status', 'posted')
            ->where('journal_entry_lines.account_id', $accountId)
            ->selectRaw('COALESCE(SUM(journal_entry_lines.debit),0) as debit, COALESCE(SUM(journal_entry_lines.credit),0) as credit')
            ->first();

        $debit = (float) ($row->debit ?? 0);
        $credit = (float) ($row->credit ?? 0);

        return $debitNormal ? $debit - $credit : $credit - $debit;
    }

    private function outstandingReceivable(): float
    {
        return (float) DB::table('customer_invoices')
            ->whereIn('status', ['sent', 'partially_paid', 'overdue'])
            ->where('amount_due', '>', 0)
            ->sum('amount_due');
    }

    private function outstandingPayable(): float
    {
        return (float) DB::table('supplier_bills')
            ->whereIn('status', ['confirmed', 'partially_paid', 'overdue'])
            ->where('amount_due', '>', 0)
            ->sum('amount_due');
    }

    private function inventoryValue(): float
    {
        // Nilai persediaan = saldo terakhir per produk/gudang × harga pokok.
        // Saldo terakhir = movement dengan (moved_at, id) terbesar, sama dengan
        // urutan rantai `balance_after` dan snapshot stok (W7-04).
        $latest = DB::table('stock_movements as sm_latest')
            ->select('sm_latest.id')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('stock_movements as sm_newer')
                    ->whereColumn('sm_newer.product_id', 'sm_latest.product_id')
                    ->whereColumn('sm_newer.warehouse_id', 'sm_latest.warehouse_id')
                    ->where(function ($inner): void {
                        $inner->whereColumn('sm_newer.moved_at', '>', 'sm_latest.moved_at')
                            ->orWhere(function ($tie): void {
                                $tie->whereColumn('sm_newer.moved_at', '=', 'sm_latest.moved_at')
                                    ->whereColumn('sm_newer.id', '>', 'sm_latest.id');
                            });
                    });
            })
            ->pluck('id');

        if ($latest->isEmpty()) {
            return 0.0;
        }

        return (float) DB::table('stock_movements as sm')
            ->join('products as p', 'p.id', '=', 'sm.product_id')
            ->whereIn('sm.id', $latest)
            ->selectRaw('COALESCE(SUM(sm.balance_after * COALESCE(p.cost, 0)), 0) as value')
            ->value('value');
    }

    private function equipmentBookValue(): float
    {
        return (float) DB::table('assets')->sum('book_value');
    }
}
