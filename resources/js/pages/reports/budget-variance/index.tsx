'use client';

import {
    createEmptyReportFilters,
    createReportBreadcrumbs,
    ReportDataTablePage,
} from '@/components/common/ReportDataTablePage';
import { budgetVarianceColumns } from '@/components/reports/budget-variance/Columns';
import { createBudgetVarianceFilterFields } from '@/components/reports/budget-variance/Filters';
import { type BudgetVarianceItem } from '@/types/budget';

export default function BudgetVarianceReportPage() {
    return (
        <ReportDataTablePage<BudgetVarianceItem>
            title="Budget Variance Report"
            breadcrumbs={createReportBreadcrumbs(
                'Budget Variance',
                '/reports/budget-variance',
            )}
            columns={budgetVarianceColumns}
            filterFields={createBudgetVarianceFilterFields()}
            initialFilters={createEmptyReportFilters([
                'budget_id',
                'status',
                'account_type',
            ] as const)}
            endpoint="/api/reports/budget-variance"
            queryKey={['budget-variance-report']}
            entityName="Budget Variance"
            exportEndpoint="/api/reports/budget-variance/export"
        />
    );
}
