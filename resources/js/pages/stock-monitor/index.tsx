'use client';

import { DataTable } from '@/components/common/DataTableCore';
import { createStockMonitorColumns, type StockMonitorItem } from '@/components/stock-monitor/Columns';
import { createStockMonitorFilterFields } from '@/components/stock-monitor/Filters';
import { StockMonitorSummaryCards } from '@/components/stock-monitor/SummaryCards';
import { useCrudFilters } from '@/hooks/useCrudFilters';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';

type SelectOption = {
    value: string;
    label: string;
};

type Props = {
    filterOptions: {
        products: SelectOption[];
        warehouses: SelectOption[];
        branches: SelectOption[];
        categories: SelectOption[];
    };
};

type StockMonitorSummary = {
    total_items: number;
    total_quantity: string;
    total_stock_value: string;
    low_stock_items: number;
    by_warehouse: { name: string; quantity: string; value: string }[];
    by_category: { name: string; quantity: string; value: string }[];
    by_branch: { name: string; quantity: string; value: string }[];
};

type StockMonitorResponse = {
    data: StockMonitorItem[];
    meta: {
        current_page: number;
        per_page: number;
        total: number;
        last_page: number;
        from?: number;
        to?: number;
    };
    summary?: StockMonitorSummary;
};

export default function StockMonitorPage({ filterOptions }: Props) {
    const columns = createStockMonitorColumns();
    const filterFields = createStockMonitorFilterFields(filterOptions);

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
            low_stock_threshold: '',
        },
    });

    const { data: response, isLoading } = useQuery<StockMonitorResponse>({
        queryKey: ['stock-monitor', pagination, filters],
        queryFn: async () => {
            const response = await axios.get('/stock-monitor', {
                params: {
                    page: pagination.page,
                    per_page: pagination.per_page,
                    ...filters,
                },
            });

            return response.data;
        },
    });
    const data = response?.data ?? [];
    const meta = response?.meta ?? {
        current_page: 1,
        per_page: pagination.per_page,
        total: 0,
        last_page: 1,
    };

    return (
        <>
            <Head title="Stock Monitor" />
            <AppLayout
                breadcrumbs={[
                    { title: 'Inventory', href: '#' },
                    { title: 'Stock Monitor', href: '/stock-monitor' },
                ]}
            >
                <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                    <StockMonitorSummaryCards summary={response?.summary} />
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
                            exportEndpoint="/api/stock-monitor/export"
                            entityName="Stock Monitor"
                        />
                    </div>
                </div>
            </AppLayout>
        </>
    );
}
