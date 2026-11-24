<?php

namespace App\Actions\AccountingPosting;

use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Models\Asset;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Jurnal perolehan aset tetap dari data register (#357).
 *
 * - Perolehan dalam fiscal year yang terbuka:
 *   Dr Equipment (12100) / Cr Bank (11110) sebesar purchase_cost, dated purchase_date.
 * - Perolehan sebelum fiscal year terbuka (aset lama):
 *   saldo awal pada hari pertama fiscal year terbuka tanpa kas:
 *   Dr Equipment (12100) = purchase_cost
 *   Cr Accumulated Depreciation (12200) = penyusutan garis lurus sampai awal fiscal year
 *   Cr Retained Earnings (32000) = neto (cost - akumulasi)
 *
 * Nilai diambil langsung dari data aset, bukan dari selisih GL vs subledger.
 * Idempoten per aset: jurnal yang sudah ada untuk referensi yang sama dikembalikan.
 */
class PostAssetAcquisitionJournalAction
{
    public const EQUIPMENT_ACCOUNT_CODE = '12100';

    public const CASH_ACCOUNT_CODE = '11110';

    public const ACCUMULATED_DEPRECIATION_ACCOUNT_CODE = '12200';

    public const RETAINED_EARNINGS_ACCOUNT_CODE = '32000';

    public function __construct(
        private CreateJournalEntryAction $createJournalEntry,
        private ResolveControlAccountAction $resolveControlAccount,
    ) {}

    /**
     * Aturan kapan aset masuk jurnal perolehan (#357):
     * - active / maintenance / lost: selalu (perolehan sudah dikapitalisasi).
     * - disposed: hanya bila diperoleh dalam fiscal year yang terbuka.
     *   Disposed sebelum fiscal year terbuka tidak masuk saldo awal.
     * - draft / cancelled: tidak dijurnal.
     */
    public function isPostable(Asset $asset): bool
    {
        if (in_array($asset->status, ['active', 'maintenance', 'lost'], true)) {
            return true;
        }

        if ($asset->status !== 'disposed') {
            return false;
        }

        $purchaseDate = Carbon::parse($asset->purchase_date)->toDateString();

        return FiscalYear::query()
            ->where('status', 'open')
            ->where('start_date', '<=', $purchaseDate)
            ->where('end_date', '>=', $purchaseDate)
            ->exists();
    }

    public function execute(Asset $asset): JournalEntry
    {
        $amount = round((float) $asset->purchase_cost, 2);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'purchase_cost' => "Asset {$asset->asset_code} must have a purchase cost greater than zero.",
            ]);
        }

        $reference = 'ACQ-' . $asset->asset_code;

        $existing = JournalEntry::query()
            ->where('reference', $reference)
            ->where('journal_type', 'system')
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return DB::transaction(function () use ($asset, $amount, $reference): JournalEntry {
            $purchaseDate = Carbon::parse($asset->purchase_date ?? now())->startOfDay();
            $coveringYear = FiscalYear::query()
                ->where('start_date', '<=', $purchaseDate->toDateString())
                ->where('end_date', '>=', $purchaseDate->toDateString())
                ->first();

            if ($coveringYear !== null && $coveringYear->isOpen()) {
                $acquisitionDate = $purchaseDate->toDateString();
                $equipment = $this->resolveControlAccount->execute(self::EQUIPMENT_ACCOUNT_CODE, $acquisitionDate);

                return $this->createJournalEntry->execute([
                    'entry_date' => $acquisitionDate,
                    'reference' => $reference,
                    'description' => "Perolehan aset {$asset->asset_code} - {$asset->name}",
                    'status' => 'posted',
                    'journal_type' => 'system',
                    'lines' => [
                        [
                            'account_id' => $equipment->id,
                            'branch_id' => $asset->branch_id,
                            'debit' => $amount,
                            'credit' => 0,
                            'memo' => 'Perolehan aset tetap',
                        ],
                        [
                            'account_id' => $this->resolveControlAccount->execute(self::CASH_ACCOUNT_CODE, $acquisitionDate)->id,
                            'branch_id' => $asset->branch_id,
                            'debit' => 0,
                            'credit' => $amount,
                            'memo' => 'Pembayaran perolehan aset',
                        ],
                    ],
                ]);
            }

            $openingYear = FiscalYear::query()
                ->where('status', 'open')
                ->orderBy('start_date')
                ->first();

            if ($openingYear === null) {
                throw ValidationException::withMessages([
                    'fiscal_year' => "No open fiscal year to post the opening balance of asset {$asset->asset_code}.",
                ]);
            }

            $openingDate = Carbon::parse($openingYear->start_date)->startOfDay();

            if ($purchaseDate->greaterThan($openingDate)) {
                throw ValidationException::withMessages([
                    'purchase_date' => "Asset {$asset->asset_code} purchase date falls in a period without an open fiscal year.",
                ]);
            }

            $openingEntryDate = $openingDate->toDateString();
            $equipment = $this->resolveControlAccount->execute(self::EQUIPMENT_ACCOUNT_CODE, $openingEntryDate);
            $accumulated = $this->accumulatedDepreciationUntil($asset, $openingDate, $amount);
            $netBookValue = round($amount - $accumulated, 2);

            $lines = [
                [
                    'account_id' => $equipment->id,
                    'branch_id' => $asset->branch_id,
                    'debit' => $amount,
                    'credit' => 0,
                    'memo' => 'Saldo awal aset tetap (harga perolehan)',
                ],
            ];

            if ($accumulated > 0) {
                $lines[] = [
                    'account_id' => $this->resolveControlAccount->execute(self::ACCUMULATED_DEPRECIATION_ACCOUNT_CODE, $openingEntryDate)->id,
                    'branch_id' => $asset->branch_id,
                    'debit' => 0,
                    'credit' => $accumulated,
                    'memo' => 'Saldo awal akumulasi penyusutan',
                ];
            }

            if ($netBookValue > 0) {
                $lines[] = [
                    'account_id' => $this->resolveControlAccount->execute(self::RETAINED_EARNINGS_ACCOUNT_CODE, $openingEntryDate)->id,
                    'branch_id' => $asset->branch_id,
                    'debit' => 0,
                    'credit' => $netBookValue,
                    'memo' => 'Saldo awal ekuitas atas aset tetap',
                ];
            }

            return $this->createJournalEntry->execute([
                'entry_date' => $openingDate->toDateString(),
                'reference' => $reference,
                'description' => "Saldo awal aset {$asset->asset_code} - {$asset->name}",
                'status' => 'posted',
                'journal_type' => 'system',
                'lines' => $lines,
            ]);
        });
    }

    /**
     * Penyusutan garis lurus dari awal depresiasi sampai tanggal $until (bulan penuh).
     * Dibatasi oleh (cost - salvage). Nilai dibulatkan ke rupiah utuh.
     */
    private function accumulatedDepreciationUntil(Asset $asset, Carbon $until, float $cost): float
    {
        $usefulLife = (int) ($asset->useful_life_months ?? 0);

        if ($usefulLife <= 0) {
            return 0.0;
        }

        $start = Carbon::parse($asset->depreciation_start_date ?? $asset->purchase_date)->startOfDay();
        $months = max(0, (int) $start->diffInMonths($until));
        $depreciable = max(0.0, $cost - (float) ($asset->salvage_value ?? 0));

        return round(min($depreciable, $depreciable / $usefulLife * $months));
    }
}
