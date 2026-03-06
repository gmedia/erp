'use client';

import { Helmet } from 'react-helmet-async';
import { DataTable } from '@/components/common/DataTableCore';
import {
    stockAdjustmentReportColumns,
    type StockAdjustmentReportItem,
} from '@/components/reports/stock-adjustment/Columns';
import { createStockAdjustmentReportFilterFields } from '@/components/reports/stock-adjustment/Filters';
import { useCrudFilters } from '@/hooks/useCrudFilters';
import { useCrudQuery } from '@/hooks/useCrudQuery';
import AppLayout from '@/layouts/app-layout';

export default function StockAdjustmentReportPage() {
    const filterFields = createStockAdjustmentReportFilterFields();

    const {
        filters,
        pagination,
        handleFilterChange,
        handleSearchChange,
        handlePageChange,
        handlePageSizeChange,
        resetFilters,
    } = useCrudFilters({
        initialFilters: {
            search: '',
            warehouse_id: '',
            branch_id: '',
            adjustment_type: '',
            status: '',
            start_date: '',
            end_date: '',
        },
    });

    const { data, isLoading, meta } = useCrudQuery<StockAdjustmentReportItem>({
        endpoint: '/reports/stock-adjustment',
        queryKey: ['stock-adjustment-report'],
        entityName: 'Stock Adjustment Report',
        pagination,
        filters,
    });

    return (
        <>
            <Helmet><title>Stock Adjustment Report</title></Helmet>
            <AppLayout
                breadcrumbs={[
                    { title: 'Reports', href: '#' },
                    { title: 'Stock Adjustment', href: '/reports/stock-adjustment' },
                ]}
            >
                <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                    <div className="rounded-lg bg-white">
                        <DataTable
                            columns={stockAdjustmentReportColumns}
                            data={data}
                            pagination={{
                                page: meta.current_page,
                                per_page: meta.per_page,
                                total: meta.total,
                                last_page: meta.last_page,
                                from: meta.from ?? 0,
                                to: meta.to ?? 0,
                            }}
                            onPageChange={handlePageChange}
                            onPageSizeChange={(perPage) =>
                                handlePageSizeChange(perPage)
                            }
                            onSearchChange={handleSearchChange}
                            isLoading={isLoading}
                            filterValue={filters.search}
                            filters={filters}
                            onFilterChange={handleFilterChange}
                            onResetFilters={resetFilters}
                            filterFields={filterFields}
                            exportEndpoint="/reports/stock-adjustment/export"
                            entityName="Stock Adjustment Report"
                        />
                    </div>
                </div>
            </AppLayout>
        </>
    );
}
