'use client';

import { DataTable } from '@/components/common/DataTableCore';
import { createStockMovementsColumns, type StockMovementItem } from '@/components/stock-movements/Columns';
import { createStockMovementsFilterFields } from '@/components/stock-movements/Filters';
import { useCrudFilters } from '@/hooks/useCrudFilters';
import { useCrudQuery } from '@/hooks/useCrudQuery';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

export default function StockMovementsPage() {
    const columns = createStockMovementsColumns();
    const filterFields = createStockMovementsFilterFields();

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
            movement_type: '',
            start_date: '',
            end_date: '',
        },
    });

    const { data, isLoading, meta } = useCrudQuery<StockMovementItem>({
        endpoint: '/stock-movements',
        queryKey: ['stock-movements'],
        entityName: 'Stock Movements',
        pagination,
        filters,
    });

    return (
        <>
            <Head title="Stock Movements" />
            <AppLayout
                breadcrumbs={[
                    { title: 'Inventory', href: '#' },
                    { title: 'Stock Movements', href: '/stock-movements' },
                ]}
            >
                <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                    <div className="rounded-lg bg-white">
                        <DataTable
                            columns={columns}
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
                            filterValue={String(filters.search ?? '')}
                            filters={filters as Record<string, string>}
                            onFilterChange={handleFilterChange}
                            onResetFilters={resetFilters}
                            filterFields={filterFields}
                            exportEndpoint="/api/stock-movements/export"
                            entityName="Stock Movement"
                        />
                    </div>
                </div>
            </AppLayout>
        </>
    );
}

