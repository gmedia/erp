<?php

namespace App\Actions\AccountingPosting;

use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Models\Asset;
use App\Models\JournalEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Jurnal aset hilang (#357, keputusan 3): nilai buku dicatat sebagai rugi pelepasan.
 *
 *   Dr Rugi pelepasan aset (52000)               = nilai buku (cost - akumulasi)
 *   Dr Accumulated Depreciation - Equipment (12200) = akumulasi penyusutan
 *   Cr Equipment (12100)                         = harga perolehan
 *
 * Jurnal diposting pada tanggal hilang, lalu akun aset dan akumulasi nol.
 * Idempoten per aset (referensi LOSS-{asset_code}). Periode tertutup -> 422.
 */
class PostAssetLossJournalAction
{
    public const EQUIPMENT_ACCOUNT_CODE = '12100';

    public const ACCUMULATED_DEPRECIATION_ACCOUNT_CODE = '12200';

    public const LOSS_ACCOUNT_CODE = '52000';

    public function __construct(
        private CreateJournalEntryAction $createJournalEntry,
        private ResolveControlAccountAction $resolveControlAccount,
    ) {}

    public function isPostable(Asset $asset): bool
    {
        return $asset->status === 'lost';
    }

    public function execute(Asset $asset): JournalEntry
    {
        $reference = 'LOSS-' . $asset->asset_code;

        $existing = JournalEntry::query()
            ->where('reference', $reference)
            ->where('journal_type', 'system')
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $cost = round((float) $asset->purchase_cost, 2);
        $accumulated = round((float) ($asset->accumulated_depreciation ?? 0), 2);
        $bookValue = round($cost - $accumulated, 2);

        if ($cost <= 0) {
            throw ValidationException::withMessages([
                'purchase_cost' => "Asset {$asset->asset_code} must have a purchase cost greater than zero.",
            ]);
        }

        return DB::transaction(function () use ($asset, $cost, $accumulated, $bookValue, $reference): JournalEntry {
            $lossDate = Carbon::now()->toDateString();

            $lines = [
                [
                    'account_id' => $this->resolveControlAccount->execute(self::LOSS_ACCOUNT_CODE)->id,
                    'branch_id' => $asset->branch_id,
                    'debit' => $bookValue,
                    'credit' => 0,
                    'memo' => 'Rugi pelepasan aset (nilai buku)',
                ],
            ];

            if ($accumulated > 0) {
                $lines[] = [
                    'account_id' => $this->resolveControlAccount->execute(self::ACCUMULATED_DEPRECIATION_ACCOUNT_CODE)->id,
                    'branch_id' => $asset->branch_id,
                    'debit' => $accumulated,
                    'credit' => 0,
                    'memo' => 'Nolkan akumulasi penyusutan',
                ];
            }

            $lines[] = [
                'account_id' => $this->resolveControlAccount->execute(self::EQUIPMENT_ACCOUNT_CODE)->id,
                'branch_id' => $asset->branch_id,
                'debit' => 0,
                'credit' => $cost,
                'memo' => 'Nolkan harga perolehan aset hilang',
            ];

            return $this->createJournalEntry->execute([
                'entry_date' => $lossDate,
                'reference' => $reference,
                'description' => "Aset hilang {$asset->asset_code} - {$asset->name}",
                'status' => 'posted',
                'journal_type' => 'system',
                'lines' => $lines,
            ]);
        });
    }
}
