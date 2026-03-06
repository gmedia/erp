'use client';

import { DataTable } from '@/components/common/DataTableCore';
import {
    purchaseHistoryReportColumns,
    type PurchaseHistoryReportItem,
} from '@/components/reports/purchase-history/Columns';
import { createPurchaseHistoryReportFilterFields } from '@/components/reports/purchase-history/Filters';
import { useCrudFilters } from '@/hooks/useCrudFilters';
import { useCrudQuery } from '@/hooks/useCrudQuery';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

export default function PurchaseHistoryReportPage() {
    const filterFields = createPurchaseHistoryReportFilterFields();

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

    const { data, isLoading, meta } = useCrudQuery<PurchaseHistoryReportItem>({
        endpoint: '/reports/purchase-history',
        queryKey: ['purchase-history-report'],
        entityName: 'Purchase History Report',
        pagination,
        filters,
    });

    return (
        <>
            <Head title="Purchase History Report" />
            <AppLayout
                breadcrumbs={[
                    { title: 'Reports', href: '#' },
                    {
                        title: 'Purchase History',
                        href: '/reports/purchase-history',
                    },
                ]}
            >
                <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                    <div className="rounded-lg bg-white">
                        <DataTable
                            columns={purchaseHistoryReportColumns}
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
                            exportEndpoint="/reports/purchase-history/export"
                            entityName="Purchase History Report"
                        />
                    </div>
                </div>
            </AppLayout>
        </>
    );
}
