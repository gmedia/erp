<?php

namespace App\Exports\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

abstract class BaseExport implements FromQuery, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithMapping, WithStyles
{
    use InteractsWithExportFilters;
    use WritesTextCells;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        protected readonly array $filters = []
    ) {}

    abstract public function query(): Builder;

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return $this->exportHeadings($this->columns());
    }

    /**
     * @return array<int, mixed>
     */
    public function map($row): array
    {
        return $this->mapExportRow($row, $this->columns());
    }

    /**
     * @return array<string, callable(mixed): mixed>
     */
    abstract protected function columns(): array;
}
