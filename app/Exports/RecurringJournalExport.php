<?php

namespace App\Exports;

use App\Exports\Concerns\InteractsWithExportFilters;
use App\Models\RecurringJournal;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class RecurringJournalExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    use InteractsWithExportFilters;

    public function __construct(protected array $filters = []) {}

    public function query(): Builder
    {
        $query = RecurringJournal::query()->with(['fiscalYear', 'creator', 'lines']);

        $this->applySearch($query, ['name', 'description']);
        $this->applyExact($query, ['frequency', 'is_active', 'fiscal_year_id']);
        $this->applyDateRange($query, 'next_run_date', 'next_run_from', 'next_run_to');

        return $query->orderBy('next_run_date');
    }

    public function headings(): array
    {
        return $this->exportHeadings($this->columns());
    }

    public function map($recurringJournal): array
    {
        return $this->mapExportRow($recurringJournal, $this->columns());
    }

    protected function columns(): array
    {
        return [
            'ID' => fn (RecurringJournal $item): mixed => $item->id,
            'Name' => fn (RecurringJournal $item): mixed => $item->name,
            'Frequency' => fn (RecurringJournal $item): mixed => $item->frequency,
            'Next Run Date' => fn (RecurringJournal $item): mixed => $this->formatDateValue($item->next_run_date, 'Y-m-d'),
            'Last Run Date' => fn (RecurringJournal $item): mixed => $this->formatDateValue($item->last_run_date, 'Y-m-d'),
            'Total Amount' => fn (RecurringJournal $item): mixed => (float) $item->total_amount,
            'Auto Post' => fn (RecurringJournal $item): mixed => $item->auto_post ? 'Yes' : 'No',
            'Active' => fn (RecurringJournal $item): mixed => $item->is_active ? 'Yes' : 'No',
            'Fiscal Year' => fn (RecurringJournal $item): mixed => $this->relatedAttribute($item, 'fiscalYear', 'name'),
            'Created By' => fn (RecurringJournal $item): mixed => $this->relatedAttribute($item, 'creator', 'name'),
        ];
    }

    private function applySearch(Builder $query, array $columns): void
    {
        if (empty($this->filters['search'])) {
            return;
        }

        $search = $this->filters['search'];
        $query->where(function (Builder $q) use ($columns, $search): void {
            foreach ($columns as $column) {
                $q->orWhere($column, 'like', "%{$search}%");
            }
        });
    }

    private function applyExact(Builder $query, array $columns): void
    {
        foreach ($columns as $column) {
            if (array_key_exists($column, $this->filters)) {
                $query->where($column, $this->filters[$column]);
            }
        }
    }

    private function applyDateRange(Builder $query, string $column, string $from, string $to): void
    {
        if (! empty($this->filters[$from])) {
            $query->whereDate($column, '>=', $this->filters[$from]);
        }

        if (! empty($this->filters[$to])) {
            $query->whereDate($column, '<=', $this->filters[$to]);
        }
    }
}
