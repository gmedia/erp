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

            $apAccount = $this->resolveControlAccount->execute(self::AP_CONTROL_ACCOUNT_CODE);

            $journalEntry = $this->createJournalEntry->execute([
                'entry_date' => $apPayment->payment_date->format('Y-m-d'),
                'reference' => $apPayment->payment_number,
                'description' => "AP Payment {$apPayment->payment_number}",
                'status' => 'posted',
                'journal_type' => 'system',
                'source_type' => ApPayment::class,
                'source_id' => $apPayment->id,
                'lines' => [
                    [
                        'account_id' => $apAccount->id,
                        'debit' => $amount,
                        'credit' => 0,
                        'memo' => "Payment {$apPayment->payment_number}",
                    ],
                    [
                        'account_id' => (int) $apPayment->bank_account_id,
                        'debit' => 0,
                        'credit' => $amount,
                        'memo' => "Payment {$apPayment->payment_number}",
                    ],
                ],
            ]);

            $apPayment->forceFill([
                'journal_entry_id' => $journalEntry->id,
            ])->save();

            return $journalEntry;
        });
    }
}
