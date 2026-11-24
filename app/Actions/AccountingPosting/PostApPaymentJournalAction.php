<?php

namespace App\Actions\AccountingPosting;

use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Models\ApPayment;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PostApPaymentJournalAction
{
    public const AP_CONTROL_ACCOUNT_CODE = '21100';

    public const AP_DISCOUNT_ACCOUNT_CODE = '51900';

    public function __construct(
        private CreateJournalEntryAction $createJournalEntry,
        private ResolveControlAccountAction $resolveControlAccount,
    ) {}

    public function execute(ApPayment $apPayment): ?JournalEntry
    {
        if ($apPayment->journal_entry_id !== null) {
            /** @var JournalEntry|null $existing */
            $existing = $apPayment->journalEntry()->first();

            return $existing;
        }

        if ($apPayment->status !== 'confirmed') {
            return null;
        }

        return DB::transaction(function () use ($apPayment): JournalEntry {
            $amount = round((float) $apPayment->total_amount, 2);

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'total_amount' => 'Cannot post a payment with non-positive total amount.',
                ]);
            }

            $entryDate = $apPayment->payment_date->format('Y-m-d');
            $apAccount = $this->resolveControlAccount->execute(self::AP_CONTROL_ACCOUNT_CODE, $entryDate);

            // Issue #437: diskon alokasi dijurnal ke akun diskon; AP didebit sebesar kas + diskon.
            $discount = round((float) $apPayment->allocations()->sum('discount_taken'), 2);
            $lines = [
                [
                    'account_id' => $apAccount->id,
                    'debit' => round($amount + $discount, 2),
                    'credit' => 0,
                    'memo' => "Payment {$apPayment->payment_number}",
                ],
                [
                    'account_id' => (int) $apPayment->bank_account_id,
                    'debit' => 0,
                    'credit' => $amount,
                    'memo' => "Payment {$apPayment->payment_number}",
                ],
            ];

            if ($discount > 0) {
                $discountAccount = $this->resolveControlAccount->execute(self::AP_DISCOUNT_ACCOUNT_CODE, $entryDate);
                $lines[] = [
                    'account_id' => $discountAccount->id,
                    'debit' => 0,
                    'credit' => $discount,
                    'memo' => "Discount {$apPayment->payment_number}",
                ];
            }

            $journalEntry = $this->createJournalEntry->execute([
                'entry_date' => $entryDate,
                'reference' => $apPayment->payment_number,
                'description' => "AP Payment {$apPayment->payment_number}",
                'status' => 'posted',
                'journal_type' => 'system',
                'source_type' => ApPayment::class,
                'source_id' => $apPayment->id,
                'branch_id' => $apPayment->branch_id,
                'lines' => $lines,
            ]);

            $apPayment->forceFill([
                'journal_entry_id' => $journalEntry->id,
            ])->save();

            return $journalEntry;
        });
    }
}
