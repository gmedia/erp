<?php

namespace App\Actions\Reports;

use App\Services\FormulaEvaluatorService;

class EvaluateReportSectionsAction
{
    public function __construct(
        private FormulaEvaluatorService $evaluator,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $sections
     * @param  array<string, float|int>  $reportTotals
     * @return array<int, array{
     *     code: string,
     *     name: string,
     *     section_type: string,
     *     value: float,
     *     formula: string|null,
     *     sort_order: int,
     * }>
     */
    public function execute(array $sections, array $reportTotals): array
    {
        $sectionData = collect($sections)->map(function (array $section) use ($reportTotals): array {
            return [
                'code' => (string) $section['code'],
                'value' => $this->resolveSectionValue($section, $reportTotals),
                'formula' => isset($section['formula']) ? (string) $section['formula'] : null,
            ];
        });

        $computedValues = $this->evaluator->evaluate($sectionData);

        return collect($sections)->map(function (array $section) use ($computedValues): array {
            $value = $computedValues[$section['code']] ?? 0.0;

            if (($section['sign_convention'] ?? 'normal') === 'reversed') {
                $value = -$value;
            }

            return [
                'code' => $section['code'],
                'name' => $section['name'],
                'section_type' => $section['section_type'],
                'value' => round($value, 2),
                'formula' => $section['formula'] ?? null,
                'sort_order' => $section['sort_order'],
            ];
        })->values()->toArray();
    }

    private function resolveSectionValue(array $section, array $reportTotals): float
    {
        if (($section['formula'] ?? null) !== null && $section['formula'] !== '') {
            return 0.0;
        }

        $sectionType = $section['section_type'];
        if ($sectionType === 'header' || $sectionType === 'separator') {
            return 0.0;
        }

        $accountCodes = $section['account_codes'] ?? null;
        if (is_array($accountCodes) && $accountCodes !== []) {
            // Section granular (mis. HPP, beban operasional) menjumlah saldo akun
            // tertentu dari pohon akun (lihat ReportConfigurationSeeder).
            $value = 0.0;
            foreach ($accountCodes as $accountCode) {
                $value += (float) ($reportTotals["account_{$accountCode}"] ?? 0);
            }

            return $value;
        }

        $accountType = $section['account_type_filter'] ?? null;
        $subType = $section['account_sub_type_filter'] ?? null;

        if ($accountType === null) {
            // Section ringkasan tanpa akun (mis. cash_inflow) mengambil nilai
            // dari total laporan yang dipasok pemanggil (W4-23).
            return (float) ($reportTotals[$section['code']] ?? 0);
        }

        if ($subType !== null) {
            return (float) ($reportTotals["{$accountType}_{$subType}"] ?? $reportTotals[$subType] ?? 0);
        }

        return (float) ($reportTotals[$this->totalKeyForType($accountType)] ?? 0);
    }

    /**
     * Nama key total per tipe akun. Bentuk jamak tidak selalu `{type}s`
     * (liability -> liabilities), sehingga dipetakan eksplisit.
     */
    private function totalKeyForType(string $accountType): string
    {
        return match ($accountType) {
            'asset' => 'assets',
            'liability' => 'liabilities',
            default => $accountType,
        };
    }
}
