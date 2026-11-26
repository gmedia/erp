<?php

namespace App\Http\Requests\Reports;

use App\Services\FinancialReportDepartmentFilter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IncomeStatementReportRequest extends FormRequest
{
    /**
     * Aturan department_id: id departemen yang ada, atau 'none' (tanpa departemen).
     * Dipakai juga oleh endpoint GET income-statement lewat ReportController.
     *
     * @return list<mixed>
     */
    public static function departmentIdRules(): array
    {
        return [
            'nullable',
            Rule::anyOf([
                Rule::in([FinancialReportDepartmentFilter::NONE]),
                'integer|exists:departments,id',
            ]),
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fiscal_year_id' => ['required', 'integer', 'exists:fiscal_years,id'],
            'comparison_year_id' => ['nullable', 'integer', 'exists:fiscal_years,id', 'different:fiscal_year_id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'department_id' => self::departmentIdRules(),
        ];
    }
}
