<?php

namespace App\Exports;

use App\Exports\Concerns\InteractsWithExportFilters;
use App\Models\BankReconciliation;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class BankReconciliationExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    use InteractsWithExportFilters;

    public function __construct(protected array $filters = []) {}

    public function query(): Builder
    {
        $query = BankReconciliation::query()->with(['account', 'fiscalYear', 'completedBy']);

        $this->applyConfiguredFilters(
            $query,
            $this->filters,
            ['notes'],
            ['status' => 'status', 'account_id' => 'account_id', 'fiscal_year_id' => 'fiscal_year_id'],
            ['reconciliation_date' => ['from' => 'date_from', 'to' => 'date_to']],
        );

        return $query->orderBy('reconciliation_date', 'desc');
    }

    public function headings(): array
    {
        return $this->exportHeadings($this->columns());
    }

    public function map($bankReconciliation): array
    {
        return $this->mapExportRow($bankReconciliation, $this->columns());
    }

    protected function columns(): array
    {
        return [
            'ID' => fn (BankReconciliation $item): mixed => $item->id,
            'Account' => fn (BankReconciliation $item): mixed => $this->relatedAttribute($item, 'account', 'name'),
            'Fiscal Year' => fn (BankReconciliation $item): mixed => $this->relatedAttribute(
                $item,
                'fiscalYear',
                'name',
            ),
            'Date' => fn (BankReconciliation $item): mixed => $this->formatDateValue(
                $item->reconciliation_date,
                'Y-m-d',
            ),
            'Statement Balance' => fn (BankReconciliation $item): mixed => (float) $item->statement_balance,
            'Book Balance' => fn (BankReconciliation $item): mixed => (float) $item->book_balance,
            'Reconciled Balance' => fn (BankReconciliation $item): mixed => (float) $item->reconciled_balance,
            'Difference' => fn (BankReconciliation $item): mixed => (float) $item->difference,
            'Status' => fn (BankReconciliation $item): mixed => $item->status,
        ];
    }
}
