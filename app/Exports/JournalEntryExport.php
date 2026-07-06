<?php

namespace App\Exports;

use App\Exports\Concerns\BaseExport;
use App\Models\JournalEntry;
use Illuminate\Database\Eloquent\Builder;

class JournalEntryExport extends BaseExport
{
    public function query(): Builder
    {
        $query = JournalEntry::query()->with(['fiscalYear', 'createdBy', 'lines']);

        if (! empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('entry_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%");
            });
        }

        if (! empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (! empty($this->filters['start_date'])) {
            $query->whereDate('entry_date', '>=', $this->filters['start_date']);
        }

        if (! empty($this->filters['end_date'])) {
            $query->whereDate('entry_date', '<=', $this->filters['end_date']);
        }

        $query->orderBy('entry_date', 'desc');

        return $query;
    }

    /**
     * @return array<string, callable(mixed): mixed>
     */
    protected function columns(): array
    {
        return [
            'ID' => fn (JournalEntry $je): mixed => $je->id,
            'Entry Number' => fn (JournalEntry $je): mixed => $je->entry_number,
            'Date' => fn (JournalEntry $je): mixed => $this->formatDateValue($je->entry_date, 'Y-m-d'),
            'Reference' => fn (JournalEntry $je): mixed => $je->reference,
            'Description' => fn (JournalEntry $je): mixed => $je->description,
            'Total Amount' => fn (JournalEntry $je): mixed => (float) $je->lines->sum('debit'),
            'Fiscal Year' => fn (JournalEntry $je): mixed => $this->relatedAttribute($je, 'fiscalYear', 'name'),
            'Status' => fn (JournalEntry $je): mixed => $je->status,
            'Created By' => fn (JournalEntry $je): mixed => $this->relatedAttribute($je, 'createdBy', 'name'),
            'Created At' => fn (JournalEntry $je): mixed => $this->formatIso8601($je->created_at),
        ];
    }
}
