<?php

namespace App\Imports;

use App\Imports\Concerns\InteractsWithImportRows;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

class BankStatementImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    use InteractsWithImportRows;

    public int $importedCount = 0;
    public int $skippedCount = 0;
    public array $errors = [];

    public function __construct(
        protected BankReconciliation $bankReconciliation,
        protected array $mapping
    ) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $rowData = $this->rowToArray($row);

            try {
                $parsed = $this->parseRow($rowData, $rowNumber);
                if ($parsed === null) {
                    continue;
                }

                BankReconciliationItem::create([
                    'bank_reconciliation_id' => $this->bankReconciliation->id,
                    'transaction_date' => $parsed['date'],
                    'description' => $parsed['description'],
                    'debit' => $parsed['debit'],
                    'credit' => $parsed['credit'],
                    'reference' => $parsed['reference'],
                    'type' => 'bank_statement',
                    'is_reconciled' => false,
                    'notes' => null,
                ]);

                $this->importedCount++;
            } catch (Throwable $exception) {
                $this->recordSystemError($rowNumber, $exception);
            }
        }
    }

    protected function parseRow(array $rowData, int $rowNumber): ?array
    {
        $dateRaw = $this->getMappedValue($rowData, 'date');
        $description = $this->getMappedValue($rowData, 'description');

        if (empty($dateRaw)) {
            $this->errors[] = ['row' => $rowNumber, 'field' => 'date', 'message' => 'Transaction date is required.'];
            $this->skippedCount++;

            return null;
        }

        if (empty($description)) {
            $this->errors[] = ['row' => $rowNumber, 'field' => 'description', 'message' => 'Description is required.'];
            $this->skippedCount++;

            return null;
        }

        $date = $this->parseDate($dateRaw);
        if ($date === null) {
            $this->errors[] = [
                'row' => $rowNumber,
                'field' => 'date',
                'message' => "Invalid date format: '{$dateRaw}'.",
            ];
            $this->skippedCount++;

            return null;
        }

        $debit = 0.0;
        $credit = 0.0;

        if (! empty($this->mapping['amount'])) {
            $amountRaw = $this->getMappedValue($rowData, 'amount');
            $amount = $this->parseNumeric($amountRaw);

            if ($amount === null) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'field' => 'amount',
                    'message' => "Invalid amount: '{$amountRaw}'.",
                ];
                $this->skippedCount++;

                return null;
            }

            if ($amount >= 0) {
                $credit = abs($amount);
            } else {
                $debit = abs($amount);
            }
        } else {
            $debitRaw = $this->getMappedValue($rowData, 'debit');
            $creditRaw = $this->getMappedValue($rowData, 'credit');
            $debit = $this->parseNumeric($debitRaw) ?? 0.0;
            $credit = $this->parseNumeric($creditRaw) ?? 0.0;
        }

        if ($debit == 0 && $credit == 0) {
            $this->errors[] = [
                'row' => $rowNumber,
                'field' => 'amount',
                'message' => 'At least one of debit or credit must be greater than zero.',
            ];
            $this->skippedCount++;

            return null;
        }

        $reference = $this->getMappedValue($rowData, 'reference');

        return [
            'date' => $date,
            'description' => trim($description),
            'debit' => round($debit, 2),
            'credit' => round($credit, 2),
            'reference' => ! empty($reference) ? trim($reference) : null,
        ];
    }

    protected function getMappedValue(array $rowData, string $mappingKey): mixed
    {
        $columnName = $this->mapping[$mappingKey] ?? null;
        if ($columnName === null) {
            return null;
        }

        $normalizedKey = str_replace(' ', '_', strtolower(trim($columnName)));

        return $rowData[$normalizedKey] ?? $rowData[$columnName] ?? null;
    }

    protected function parseDate(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(
                    ExcelDate::excelToDateTimeObject((int) $value)
                )->format('Y-m-d');
            } catch (Throwable) {
                return null;
            }
        }

        $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'd.m.Y', 'Y/m/d'];

        foreach ($formats as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, trim($value));
                if ($parsed !== null && $parsed->format($format) === trim($value)) {
                    return $parsed->format('Y-m-d');
                }
            } catch (Throwable) {
                continue;
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (Throwable) {
            return null;
        }
    }

    protected function parseNumeric(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $cleaned = str_replace([',', ' '], ['', ''], (string) $value);

        if (! is_numeric($cleaned)) {
            return null;
        }

        return (float) $cleaned;
    }
}
