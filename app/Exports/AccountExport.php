<?php

namespace App\Exports;

use App\Models\Account;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AccountExport implements FromCollection, WithHeadings, WithMapping
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Account::query();
        
        if (!empty($this->filters['coa_version_id'])) {
            $query->where('coa_version_id', $this->filters['coa_version_id']);
        }
        
        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }
        
        if (!empty($this->filters['type'])) {
            $query->where('type', $this->filters['type']);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Code',
            'Name',
            'Type',
            'Normal Balance',
            'Active',
            'Level',
        ];
    }

    public function map($account): array
    {
        return [
            $account->id,
            $account->code,
            $account->name,
            $account->type,
            $account->normal_balance,
            $account->is_active ? 'Yes' : 'No',
            $account->level,
        ];
    }
}
