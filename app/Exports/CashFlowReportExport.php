<?php

namespace App\Exports;

use App\Exports\Concerns\ExportsNumberFormats;
use App\Exports\Concerns\WritesTextCells;
use App\Services\FinancialReportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CashFlowReportExport implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithCustomValueBinder, WithHeadings
{
    use ExportsNumberFormats;
    use WritesTextCells;

    public function __construct(
        private array $filters,
    ) {}

    public function collection(): Collection
    {
        $branchId = isset($this->filters['branch_id']) && $this->filters['branch_id'] !== ''
            ? (int) $this->filters['branch_id']
            : null;

        $rows = app(FinancialReportService::class)->getCashFlow((int) $this->filters['fiscal_year_id'], $branchId);

        $accountRows = collect($rows)->map(fn (array $row): array => [
            $row['code'],
            $row['name'],
            $row['type'],
            $row['normal_balance'],
            $row['level'],
            $this->numericValue($row['inflow']),
            $this->numericValue($row['outflow']),
            $this->numericValue($row['inflow']) - $this->numericValue($row['outflow']),
        ]);

        return $accountRows->concat($this->footerRows());
    }

    /**
     * Ringkasan di bawah rincian akun: saldo awal, tiga aktivitas, kenaikan
     * bersih, dan saldo akhir. Kolom label di kolom pertama.
     *
     * @return array<int, array<int, mixed>>
     */
    public function footerRows(): array
    {
        $branchId = isset($this->filters['branch_id']) && $this->filters['branch_id'] !== ''
            ? (int) $this->filters['branch_id']
            : null;
        $fiscalYearId = (int) $this->filters['fiscal_year_id'];

        $service = app(FinancialReportService::class);
        $opening = $service->getOpeningCashBalance($fiscalYearId, $branchId);
        $totals = $service->getCashFlowTotals($fiscalYearId, $branchId);

        return [
            ['Operating activities', '', '', '', '', '', '', $this->numericValue($totals['operating_cash_flow'])],
            ['Investing activities', '', '', '', '', '', '', $this->numericValue($totals['investing_cash_flow'])],
            ['Financing activities', '', '', '', '', '', '', $this->numericValue($totals['financing_cash_flow'])],
            ['Opening cash', '', '', '', '', '', '', $this->numericValue($opening)],
            ['Net change', '', '', '', '', '', '', $this->numericValue($totals['net_change'])],
            ['Closing cash', '', '', '', '', '', '', round($opening + $totals['net_change'], 2)],
        ];
    }

    public function headings(): array
    {
        return ['Code', 'Name', 'Type', 'Normal Balance', 'Level', 'Inflow', 'Outflow', 'Net'];
    }

    /**
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        return $this->buildColumnFormats($this->headings());
    }

    /**
     * @return array<int, string>
     */
    protected function currencyColumnHeadings(): array
    {
        return ['Inflow', 'Outflow', 'Net'];
    }
}
