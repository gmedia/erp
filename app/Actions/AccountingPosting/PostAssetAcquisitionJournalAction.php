<?php

namespace App\Actions\AccountingPosting;

use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Models\Account;
use App\Models\Asset;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Jurnal perolehan aset tetap dari data register (nilai tetap = purchase_cost).
 *
 * Dr Equipment (12100, per cabang) / Cr Bank (11110) sebesar purchase_cost.
 * Nilai diambil langsung dari aset, bukan dari selisih GL vs subledger (#357).
 * Idempoten per aset: jurnal yang sudah ada untuk referensi yang sama dikembalikan.
 */
class PostAssetAcquisitionJournalAction
{
    public const EQUIPMENT_ACCOUNT_CODE = '12100';

    public const CASH_ACCOUNT_CODE = '11110';

    public function __construct(
        private CreateJournalEntryAction $createJournalEntry,
    ) {}

    public function execute(Asset $asset): JournalEntry
    {
        $amount = round((float) $asset->purchase_cost, 2);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'purchase_cost' => "Asset {$asset->asset_code} must have a purchase cost greater than zero.",
            ]);
        }

        $reference = 'ACQ-'.$asset->asset_code;

        $existing = JournalEntry::query()
            ->where('reference', $reference)
            ->where('journal_type', 'system')
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return DB::transaction(function () use ($asset, $amount, $reference): JournalEntry {
            $equipmentAccount = Account::query()->where('code', self::EQUIPMENT_ACCOUNT_CODE)->firstOrFail();
            $cashAccount = Account::query()->where('code', self::CASH_ACCOUNT_CODE)->firstOrFail();

            return $this->createJournalEntry->execute([
                'entry_date' => ($asset->purchase_date ?? now())->format('Y-m-d'),
                'reference' => $reference,
                'description' => "Perolehan aset {$asset->asset_code} - {$asset->name}",
                'status' => 'posted',
                'journal_type' => 'system',
                'lines' => [
                    [
                        'account_id' => $equipmentAccount->id,
                        'branch_id' => $asset->branch_id,
                        'debit' => $amount,
                        'credit' => 0,
                        'memo' => 'Perolehan aset tetap',
                    ],
                    [
                        'account_id' => $cashAccount->id,
                        'branch_id' => $asset->branch_id,
                        'debit' => 0,
                        'credit' => $amount,
                        'memo' => 'Pembayaran perolehan aset',
                    ],
                ],
            ]);
        });
    }
}
