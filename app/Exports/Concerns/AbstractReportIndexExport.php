<?php

namespace App\Exports\Concerns;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

abstract class AbstractReportIndexExport implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithCustomValueBinder, WithStyles
{
    use ExportsNumberFormats;
    use WritesTextCells;

    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
        $this->filters['export'] = true;
    }

    public function collection()
    {
        $action = app($this->actionClass());
        $requestClass = $this->requestClass();
        $request = new $requestClass;
        $request->merge($this->filters);

        return $action->execute($request);
    }

    /**
     * Laporan mendeklarasikan header lewat headings(), bukan columns().
     *
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        return $this->buildColumnFormats($this->headings());
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    /**
     * @return array<int, string>
     */
    abstract public function headings(): array;

    /**
     * @return class-string
     */
    abstract protected function actionClass(): string;

    /**
     * @return class-string
     */
    abstract protected function requestClass(): string;
}
