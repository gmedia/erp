'use client';

import { DataTablePage } from '@/components/common/DataTablePage';
import {
    createStockMovementsColumns,
    type StockMovementItem,
} from '@/components/stock-movements/Columns';
import { createStockMovementsFilterFields } from '@/components/stock-movements/Filters';
import { useCrudFilters } from '@/hooks/useCrudFilters';
import { useCrudQuery } from '@/hooks/useCrudQuery';

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
        endpoint: '/api/stock-movements',
        queryKey: ['stock-movements'],
        entityName: 'Stock Movements',
        pagination,
        filters,
    });

    return (
        <DataTablePage<StockMovementItem, Record<string, string>>
            title="Stock Movements"
            breadcrumbs={[
                { title: 'Inventory', href: '#' },
                { title: 'Stock Movements', href: '/stock-movements' },
            ]}
            columns={columns}
            data={data}
            meta={meta}
            isLoading={isLoading}
            filterValue={String(filters.search ?? '')}
            filters={filters as Record<string, string>}
            filterFields={filterFields}
            exportEndpoint="/api/stock-movements/export"
            entityName="Stock Movement"
            onPageChange={handlePageChange}
            onPageSizeChange={handlePageSizeChange}
            onSearchChange={handleSearchChange}
            onFilterChange={handleFilterChange}
            onResetFilters={resetFilters}
        />
    );
}
