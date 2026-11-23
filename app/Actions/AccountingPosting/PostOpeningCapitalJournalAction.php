<?php

namespace App\Actions\AccountingPosting;

use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Models\Account;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Jurnal saldo awal modal disetor: nilai tetap, bukan hasil selisih GL vs subledger.
 *
 * Dr Bank (11110) / Cr Modal disetor (31000) sebesar nominal yang diberikan.
 * Idempoten: entri yang sudah ada untuk referensi yang sama dikembalikan apa adanya.
 */
class PostOpeningCapitalJournalAction
{
    public const BANK_ACCOUNT_CODE = '11110';

    public const PAID_IN_CAPITAL_CODE = '31000';

    public function __construct(
        private CreateJournalEntryAction $createJournalEntry,
    ) {}

    public function execute(string $entryDate, float $amount, string $reference, string $description): JournalEntry
    {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Opening capital amount must be greater than zero.',
            ]);
        }

        $existing = JournalEntry::query()
            ->where('reference', $reference)
            ->where('journal_type', 'system')
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return DB::transaction(function () use ($entryDate, $amount, $reference, $description): JournalEntry {
            $bankAccount = Account::query()->where('code', self::BANK_ACCOUNT_CODE)->firstOrFail();
            $capitalAccount = Account::query()->where('code', self::PAID_IN_CAPITAL_CODE)->firstOrFail();

            return $this->createJournalEntry->execute([
                'entry_date' => $entryDate,
                'reference' => $reference,
                'description' => $description,
                'status' => 'posted',
                'journal_type' => 'system',
                'lines' => [
                    [
                        'account_id' => $bankAccount->id,
                        'debit' => $amount,
                        'credit' => 0,
                        'memo' => 'Penerimaan setoran modal awal',
                    ],
                    [
                        'account_id' => $capitalAccount->id,
                        'debit' => 0,
                        'credit' => $amount,
                        'memo' => 'Modal disetor pemilik',
                    ],
                ],
            ]);
        });
    }
}
