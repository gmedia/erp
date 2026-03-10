'use client';

import { DataTable } from '@/components/common/DataTableCore';
import {
    purchaseOrderStatusReportColumns,
    type PurchaseOrderStatusReportItem,
} from '@/components/reports/purchase-order-status/Columns';
import { createPurchaseOrderStatusReportFilterFields } from '@/components/reports/purchase-order-status/Filters';
import { useCrudFilters } from '@/hooks/useCrudFilters';
import { useCrudQuery } from '@/hooks/useCrudQuery';
import AppLayout from '@/layouts/app-layout';
import { Helmet } from 'react-helmet-async';

export default function PurchaseOrderStatusReportPage() {
    const filterFields = createPurchaseOrderStatusReportFilterFields();

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
            status_category: '',
            start_date: '',
            end_date: '',
        },
    });

    const { data, isLoading, meta } = useCrudQuery<PurchaseOrderStatusReportItem>({
        endpoint: '/api/reports/purchase-order-status',
        queryKey: ['purchase-order-status-report'],
        entityName: 'Purchase Order Status Report',
        pagination,
        filters,
    });

    return (
        <>
            <Helmet>
                <title>Purchase Order Status Report</title>
            </Helmet>
            <AppLayout
                breadcrumbs={[
                    { title: 'Reports', href: '#' },
                    {
                        title: 'Purchase Order Status',
                        href: '/reports/purchase-order-status',
                    },
                ]}
            >
                <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                    <div className="rounded-lg bg-white">
                        <DataTable
                            columns={purchaseOrderStatusReportColumns}
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
                            exportEndpoint="/api/reports/purchase-order-status/export"
                            entityName="Purchase Order Status Report"
                        />
                    </div>
                </div>
            </AppLayout>
        </>
    );
}
