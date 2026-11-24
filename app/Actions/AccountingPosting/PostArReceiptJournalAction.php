<?php

namespace App\Actions\AccountingPosting;

use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Models\ArReceipt;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PostArReceiptJournalAction
{
    public const AR_CONTROL_ACCOUNT_CODE = '11200';

    public const AR_DISCOUNT_ACCOUNT_CODE = '41900';

    public function __construct(
        private CreateJournalEntryAction $createJournalEntry,
        private ResolveControlAccountAction $resolveControlAccount,
    ) {}

    public function execute(ArReceipt $arReceipt): ?JournalEntry
    {
        if ($arReceipt->journal_entry_id !== null) {
            /** @var JournalEntry|null $existing */
            $existing = $arReceipt->journalEntry()->first();

            return $existing;
        }

        if ($arReceipt->status !== 'confirmed') {
            return null;
        }

        return DB::transaction(function () use ($arReceipt): JournalEntry {
            $amount = round((float) $arReceipt->total_amount, 2);

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'total_amount' => 'Cannot post a receipt with non-positive total amount.',
                ]);
            }

            $entryDate = $arReceipt->receipt_date->format('Y-m-d');
            $arAccount = $this->resolveControlAccount->execute(self::AR_CONTROL_ACCOUNT_CODE, $entryDate);

            // Issue #437: diskon alokasi dijurnal ke akun diskon; AR dikredit sebesar kas + diskon.
            $discount = round((float) $arReceipt->allocations()->sum('discount_given'), 2);
            $lines = [
                [
                    'account_id' => (int) $arReceipt->bank_account_id,
                    'debit' => $amount,
                    'credit' => 0,
                    'memo' => "Receipt {$arReceipt->receipt_number}",
                ],
                [
                    'account_id' => $arAccount->id,
                    'debit' => 0,
                    'credit' => round($amount + $discount, 2),
                    'memo' => "Receipt {$arReceipt->receipt_number}",
                ],
            ];

            if ($discount > 0) {
                $discountAccount = $this->resolveControlAccount->execute(self::AR_DISCOUNT_ACCOUNT_CODE, $entryDate);
                $lines[] = [
                    'account_id' => $discountAccount->id,
                    'debit' => $discount,
                    'credit' => 0,
                    'memo' => "Discount {$arReceipt->receipt_number}",
                ];
            }

            $journalEntry = $this->createJournalEntry->execute([
                'entry_date' => $entryDate,
                'reference' => $arReceipt->receipt_number,
                'description' => "AR Receipt {$arReceipt->receipt_number}",
                'status' => 'posted',
                'journal_type' => 'system',
                'source_type' => ArReceipt::class,
                'source_id' => $arReceipt->id,
                'branch_id' => $arReceipt->branch_id,
                'lines' => $lines,
            ]);

            $arReceipt->forceFill([
                'journal_entry_id' => $journalEntry->id,
            ])->save();

            return $journalEntry;
        });
    }
}
