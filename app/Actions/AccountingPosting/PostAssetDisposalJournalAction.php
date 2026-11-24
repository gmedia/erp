<?php

namespace App\Actions\AccountingPosting;

use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Models\Asset;
use App\Models\JournalEntry;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Jurnal pelepasan aset tanpa hasil penjualan (#442).
 *
 *   Dr Accumulated Depreciation - Equipment (12200) = akumulasi penyusutan
 *   Dr Rugi pelepasan aset (Setting asset_disposal_loss_account_id) = nilai buku
 *   Cr Equipment (12100)                            = harga perolehan
 *
 * Nilai buku (cost - akumulasi) dicatat sebagai rugi. Akun laba/rugi diambil dari
 * pengaturan, tidak di-hardcode. Idempoten per aset (referensi DISP-{asset_code}).
 * Periode tertutup -> 422 (dari ResolveControlAccountAction).
 */
class PostAssetDisposalJournalAction
{
    public const EQUIPMENT_ACCOUNT_CODE = '12100';

    public const ACCUMULATED_DEPRECIATION_ACCOUNT_CODE = '12200';

    public const LOSS_SETTING_KEY = 'asset_disposal_loss_account_id';

    public const GAIN_SETTING_KEY = 'asset_disposal_gain_account_id';

    public function __construct(
        private CreateJournalEntryAction $createJournalEntry,
        private ResolveControlAccountAction $resolveControlAccount,
    ) {}

    public function isPostable(Asset $asset): bool
    {
        return in_array($asset->status, ['disposed', 'lost'], true);
    }

    public function execute(Asset $asset, string $disposalDate): JournalEntry
    {
        $reference = 'DISP-' . $asset->asset_code;

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

        $lossAccountId = $this->configuredAccountId(self::LOSS_SETTING_KEY);

        return DB::transaction(function () use (
            $asset,
            $cost,
            $accumulated,
            $bookValue,
            $disposalDate,
            $reference,
            $lossAccountId,
        ): JournalEntry {
            $entryDate = Carbon::parse($disposalDate)->toDateString();
            $accumulatedAccountId = $this->resolveControlAccount
                ->execute(self::ACCUMULATED_DEPRECIATION_ACCOUNT_CODE, $entryDate)
                ->id;

            $lines = [];

            if ($accumulated > 0) {
                $lines[] = [
                    'account_id' => $accumulatedAccountId,
                    'branch_id' => $asset->branch_id,
                    'debit' => $accumulated,
                    'credit' => 0,
                    'memo' => 'Nolkan akumulasi penyusutan',
                ];
            }

            if ($bookValue > 0) {
                $lines[] = [
                    'account_id' => $lossAccountId,
                    'branch_id' => $asset->branch_id,
                    'debit' => $bookValue,
                    'credit' => 0,
                    'memo' => 'Rugi pelepasan aset (nilai buku)',
                ];
            }

            $lines[] = [
                'account_id' => $this->resolveControlAccount->execute(self::EQUIPMENT_ACCOUNT_CODE, $entryDate)->id,
                'branch_id' => $asset->branch_id,
                'debit' => 0,
                'credit' => $cost,
                'memo' => 'Nolkan harga perolehan aset',
            ];

            return $this->createJournalEntry->execute([
                'entry_date' => $entryDate,
                'reference' => $reference,
                'description' => "Pelepasan aset {$asset->asset_code} - {$asset->name}",
                'status' => 'posted',
                'journal_type' => 'system',
                'lines' => $lines,
            ]);
        });
    }

    private function configuredAccountId(string $settingKey): int
    {
        $value = (string) Setting::get($settingKey);

        if ($value === '' || ! ctype_digit($value)) {
            throw ValidationException::withMessages([
                'account' => __('accounting_posting.disposal_accounts_missing'),
            ]);
        }

        return (int) $value;
    }
}
