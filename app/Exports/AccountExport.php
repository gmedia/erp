<?php

namespace App\Exports;

use App\Exports\Concerns\InteractsWithExportFilters;
use App\Models\Account;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class AccountExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    use InteractsWithExportFilters;

    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Account::query();

        if (! empty($this->filters['coa_version_id'])) {
            $query->where('coa_version_id', $this->filters['coa_version_id']);
        }

        if (! empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if (! empty($this->filters['type'])) {
            $query->where('type', $this->filters['type']);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return $this->exportHeadings($this->columns());
    }

    public function map($account): array
    {
        return $this->mapExportRow($account, $this->columns());
    }

    /**
     * @return array<string, callable(mixed): mixed>
     */
    protected function columns(): array
    {
        return [
            'ID' => fn (Account $a): mixed => $a->id,
            'Code' => fn (Account $a): mixed => $a->code,
            'Name' => fn (Account $a): mixed => $a->name,
            'Type' => fn (Account $a): mixed => $a->type,
            'Normal Balance' => fn (Account $a): mixed => $a->normal_balance,
            'Active' => fn (Account $a): mixed => $a->is_active ? 'Yes' : 'No',
            'Level' => fn (Account $a): mixed => $a->level,
        ];
    }
}
