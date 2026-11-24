<?php

namespace App\Actions\RecurringJournals;

use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Models\JournalEntry;
use App\Models\RecurringJournal;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExecuteRecurringJournalAction
{
    public function __construct(
        private CreateJournalEntryAction $createJournalEntry,
    ) {}

    /**
     * Membuat jurnal dari template. Jurnal lewat CreateJournalEntryAction sehingga draft dan
     * auto_post sama-sama memeriksa periode, tahun fiskal, dan akun aktif. last_run_date dan
     * next_run_date hanya maju bila jurnal berhasil dibuat (satu transaksi).
     */
    public function execute(RecurringJournal $recurringJournal): JournalEntry
    {
        return DB::transaction(function () use ($recurringJournal): JournalEntry {
            $recurringJournal->load(['lines.account', 'fiscalYear']);

            if (! $recurringJournal->is_active) {
                throw ValidationException::withMessages(['recurring_journal' => __('recurring_journal.inactive')]);
            }

            if (! $recurringJournal->isBalanced()) {
                throw ValidationException::withMessages(['lines' => __('recurring_journal.lines_not_balanced')]);
            }

            // Tahun fiskal jurnal ditentukan tanggal entry (CreateJournalEntryAction), bukan template.
            $executedAt = now();

            $reference = $this->renderTemplate($recurringJournal->reference_template, $executedAt);
            $description = $this->renderTemplate($recurringJournal->description, $executedAt);

            if ($description === null || $description === '') {
                $description = $recurringJournal->name;
            }

            $entry = $this->createJournalEntry->execute([
                'entry_number_prefix' => 'RJ-',
                'validate_accounts' => true,
                'entry_date' => $executedAt->toDateString(),
                'reference' => $reference,
                'description' => $description,
                'status' => $recurringJournal->auto_post ? 'posted' : 'draft',
                'journal_type' => 'recurring',
                'source_type' => RecurringJournal::class,
                'source_id' => $recurringJournal->id,
                'lines' => $recurringJournal->lines->map(fn ($line): array => [
                    'account_id' => $line->account_id,
                    'branch_id' => $line->branch_id ?? null,
                    'debit' => $line->debit,
                    'credit' => $line->credit,
                    'memo' => $line->memo,
                ])->all(),
            ]);

            $recurringJournal->update([
                'last_run_date' => $executedAt->toDateString(),
                'next_run_date' => $this->nextRunDate($recurringJournal),
            ]);

            return $entry->load(['lines.account', 'fiscalYear', 'createdBy', 'postedBy']);
        });
    }

    private function renderTemplate(?string $template, CarbonInterface $date): ?string
    {
        if ($template === null || $template === '') {
            return null;
        }

        return str_replace(
            ['{YYYY}', '{MM}', '{DD}'],
            [$date->format('Y'), $date->format('m'), $date->format('d')],
            $template,
        );
    }

    private function nextRunDate(RecurringJournal $recurringJournal): CarbonInterface
    {
        $date = $recurringJournal->next_run_date->copy();

        return match ($recurringJournal->frequency) {
            'daily' => $date->addDay(),
            'weekly' => $date->addWeek(),
            'quarterly' => $date->addQuarter(),
            'annual' => $date->addYear(),
            default => $date->addMonth(),
        };
    }
}
