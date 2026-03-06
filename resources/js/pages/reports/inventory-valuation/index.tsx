'use client';

import { Helmet } from 'react-helmet-async';
import { DataTable } from '@/components/common/DataTableCore';
import {
    createInventoryValuationFilterFields,
} from '@/components/reports/inventory-valuation/Filters';
import {
    inventoryValuationColumns,
    type InventoryValuationItem,
} from '@/components/reports/inventory-valuation/Columns';
import { useCrudFilters } from '@/hooks/useCrudFilters';
import { useCrudQuery } from '@/hooks/useCrudQuery';
import AppLayout from '@/layouts/app-layout';

export default function InventoryValuationReportPage() {
    const filterFields = createInventoryValuationFilterFields();

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
        },
    });

    const { data, isLoading, meta } = useCrudQuery<InventoryValuationItem>({
        endpoint: '/reports/inventory-valuation',
        queryKey: ['inventory-valuation-report'],
        entityName: 'Inventory Valuation Report',
        pagination,
        filters,
    });

    return (
        <>
            <Helmet><title>Inventory Valuation Report</title></Helmet>
            <AppLayout
                breadcrumbs={[
                    { title: 'Reports', href: '#' },
                    { title: 'Inventory Valuation', href: '/reports/inventory-valuation' },
                ]}
            >
                <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                    <div className="rounded-lg bg-white">
                        <DataTable
                            columns={inventoryValuationColumns}
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
                            exportEndpoint="/reports/inventory-valuation/export"
                            entityName="Inventory Valuation"
                        />
                    </div>
                </div>
            </AppLayout>
        </>
    );
}
