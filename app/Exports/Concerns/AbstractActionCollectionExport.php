<?php

namespace App\Exports\Concerns;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

abstract class AbstractActionCollectionExport implements FromCollection, ShouldAutoSize, WithStyles
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(protected array $filters = [])
    {
        $this->filters = $this->prepareFilters($filters);
    }

    public function collection(): Collection
    {
        $action = app($this->actionClass());
        $requestClass = $this->requestClass();
        $request = new $requestClass;
        $request->merge($this->filters);

        return $this->transformActionResult($action->execute($request));
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function prepareFilters(array $filters): array
    {
        return $filters;
    }

    protected function transformActionResult(mixed $result): Collection
    {
        if ($result instanceof Collection) {
            return $result;
        }

        return collect($result);
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
