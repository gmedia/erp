'use client';

import { DataTable } from '@/components/common/DataTableCore';
import {
    createInventoryStocktakeVarianceFilterFields,
} from '@/components/reports/inventory-stocktake-variance/Filters';
import {
    inventoryStocktakeVarianceColumns,
    type InventoryStocktakeVarianceReportItem,
} from '@/components/reports/inventory-stocktake-variance/Columns';
import { useCrudFilters } from '@/hooks/useCrudFilters';
import { useCrudQuery } from '@/hooks/useCrudQuery';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

export default function InventoryStocktakeVarianceReportPage() {
    const filterFields = createInventoryStocktakeVarianceFilterFields();

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
            inventory_stocktake_id: '',
            product_id: '',
            warehouse_id: '',
            branch_id: '',
            category_id: '',
            result: '',
            start_date: '',
            end_date: '',
        },
    });

    const { data, isLoading, meta } = useCrudQuery<InventoryStocktakeVarianceReportItem>({
        endpoint: '/reports/inventory-stocktake-variance',
        queryKey: ['inventory-stocktake-variance-report'],
        entityName: 'Inventory Stocktake Variance Report',
        pagination,
        filters,
    });

    return (
        <>
            <Head title="Inventory Stocktake Variance Report" />
            <AppLayout
                breadcrumbs={[
                    { title: 'Reports', href: '#' },
                    { title: 'Inventory Stocktake Variance', href: '/reports/inventory-stocktake-variance' },
                ]}
            >
                <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                    <div className="rounded-lg bg-white">
                        <DataTable
                            columns={inventoryStocktakeVarianceColumns}
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
                            exportEndpoint="/reports/inventory-stocktake-variance/export"
                            entityName="Inventory Stocktake Variance"
                        />
                    </div>
                </div>
            </AppLayout>
        </>
    );
}
