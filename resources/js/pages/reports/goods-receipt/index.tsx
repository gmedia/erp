'use client';

import { DataTable } from '@/components/common/DataTableCore';
import {
    goodsReceiptReportColumns,
    type GoodsReceiptReportItem,
} from '@/components/reports/goods-receipt/Columns';
import { createGoodsReceiptReportFilterFields } from '@/components/reports/goods-receipt/Filters';
import { useCrudFilters } from '@/hooks/useCrudFilters';
import { useCrudQuery } from '@/hooks/useCrudQuery';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

export default function GoodsReceiptReportPage() {
    const filterFields = createGoodsReceiptReportFilterFields();

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
            supplier_id: '',
            warehouse_id: '',
            product_id: '',
            status: '',
            start_date: '',
            end_date: '',
        },
    });

    const { data, isLoading, meta } = useCrudQuery<GoodsReceiptReportItem>({
        endpoint: '/reports/goods-receipt',
        queryKey: ['goods-receipt-report'],
        entityName: 'Goods Receipt Report',
        pagination,
        filters,
    });

    return (
        <>
            <Head title="Goods Receipt Report" />
            <AppLayout
                breadcrumbs={[
                    { title: 'Reports', href: '#' },
                    {
                        title: 'Goods Receipt',
                        href: '/reports/goods-receipt',
                    },
                ]}
            >
                <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                    <div className="rounded-lg bg-white">
                        <DataTable
                            columns={goodsReceiptReportColumns}
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
                            exportEndpoint="/reports/goods-receipt/export"
                            entityName="Goods Receipt Report"
                        />
                    </div>
                </div>
            </AppLayout>
        </>
    );
}
