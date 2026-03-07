'use client';

import { Helmet } from 'react-helmet-async';
import { DataTable } from '@/components/common/DataTableCore';
import {
    stockMovementReportColumns,
    type StockMovementReportItem,
} from '@/components/reports/stock-movement/Columns';
import { createStockMovementReportFilterFields } from '@/components/reports/stock-movement/Filters';
import { useCrudFilters } from '@/hooks/useCrudFilters';
import { useCrudQuery } from '@/hooks/useCrudQuery';
import AppLayout from '@/layouts/app-layout';

export default function StockMovementReportPage() {
    const filterFields = createStockMovementReportFilterFields();

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
            product_id: '',
            warehouse_id: '',
            branch_id: '',
            category_id: '',
            start_date: '',
            end_date: '',
        },
    });

    const { data, isLoading, meta } = useCrudQuery<StockMovementReportItem>({
        endpoint: '/api/reports/stock-movement',
        queryKey: ['stock-movement-report'],
        entityName: 'Stock Movement Report',
        pagination,
        filters,
    });

    return (
        <>
            <Helmet><title>Stock Movement Report</title></Helmet>
            <AppLayout
                breadcrumbs={[
                    { title: 'Reports', href: '#' },
                    { title: 'Stock Movement', href: '/reports/stock-movement' },
                ]}
            >
                <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                    <div className="rounded-lg bg-white">
                        <DataTable
                            columns={stockMovementReportColumns}
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
                            exportEndpoint="/api/reports/stock-movement/export"
                            entityName="Stock Movement Report"
                        />
                    </div>
                </div>
            </AppLayout>
        </>
    );
}
