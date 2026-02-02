<?php

namespace App\Exports;

use App\Models\JournalEntry;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class JournalEntryExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        $query = JournalEntry::query()->with(['fiscalYear', 'createdBy']);

        if (!empty($this->filters['search'])) {
             $search = $this->filters['search'];
             $query->where(function($q) use ($search) {
                 $q->where('entry_number', 'like', "%{$search}%")
                   ->orWhere('description', 'like', "%{$search}%")
                   ->orWhere('reference', 'like', "%{$search}%");
             });
        }

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }
        
        if (!empty($this->filters['start_date'])) {
            $query->whereDate('entry_date', '>=', $this->filters['start_date']);
        }

        if (!empty($this->filters['end_date'])) {
            $query->whereDate('entry_date', '<=', $this->filters['end_date']);
        }

        $query->orderBy('entry_date', 'desc');

        return $query;
    }

    public function headings(): array
    {
        return ['ID', 'Entry Number', 'Date', 'Reference', 'Description', 'Fiscal Year', 'Status', 'Created By', 'Created At'];
    }

    public function map($journalEntry): array
    {
        return [
            $journalEntry->id,
            $journalEntry->entry_number,
            $journalEntry->entry_date->format('Y-m-d'),
            $journalEntry->reference,
            $journalEntry->description,
            $journalEntry->fiscalYear->name ?? '',
            $journalEntry->status,
            $journalEntry->createdBy->name ?? '',
            $journalEntry->created_at->toIso8601String(),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
