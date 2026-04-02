<?php

namespace App\Exports\Concerns;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

abstract class AbstractReportIndexExport implements FromCollection, ShouldAutoSize, WithStyles
{
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

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    /**
     * @return class-string
     */
    abstract protected function actionClass(): string;

    /**
     * @return class-string
     */
    abstract protected function requestClass(): string;
}
