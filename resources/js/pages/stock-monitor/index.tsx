'use client';

import { DataTable } from '@/components/common/DataTableCore';
import {
    createStockMonitorColumns,
    type StockMonitorItem,
} from '@/components/stock-monitor/Columns';
import { createStockMonitorFilterFields } from '@/components/stock-monitor/Filters';
import { StockMonitorSummaryCards } from '@/components/stock-monitor/SummaryCards';
import { useCrudFilters } from '@/hooks/useCrudFilters';
import AppLayout from '@/layouts/app-layout';
import axios from '@/lib/axios';
import { useQuery } from '@tanstack/react-query';
import { useEffect, useMemo, useState } from 'react';
import { Helmet } from 'react-helmet-async';

type SelectOption = {
    value: string;
    label: string;
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

export default function StockMonitorPage() {
    const [filterOptions, setFilterOptions] = useState<{
        products: SelectOption[];
        warehouses: SelectOption[];
        branches: SelectOption[];
        categories: SelectOption[];
    }>({ products: [], warehouses: [], branches: [], categories: [] });

    useEffect(() => {
        const fetchOptions = async () => {
            try {
                const [products, warehouses, branches, categories] =
                    await Promise.all([
                        axios.get('/api/products', {
                            params: { per_page: 100 },
                        }),
                        axios.get('/api/warehouses', {
                            params: { per_page: 100 },
                        }),
                        axios.get('/api/branches', {
                            params: { per_page: 100 },
                        }),
                        axios.get('/api/product-categories', {
                            params: { per_page: 100 },
                        }),
                    ]);
                setFilterOptions({
                    products: (products.data.data || []).map(
                        (p: { id: number; name: string }) => ({
                            value: String(p.id),
                            label: p.name,
                        }),
                    ),
                    warehouses: (warehouses.data.data || []).map(
                        (w: { id: number; name: string }) => ({
                            value: String(w.id),
                            label: w.name,
                        }),
                    ),
                    branches: (branches.data.data || []).map(
                        (b: { id: number; name: string }) => ({
                            value: String(b.id),
                            label: b.name,
                        }),
                    ),
                    categories: (categories.data.data || []).map(
                        (c: { id: number; name: string }) => ({
                            value: String(c.id),
                            label: c.name,
                        }),
                    ),
                });
            } catch (e) {
                console.error('Failed to load filter options', e);
            }
        };
        fetchOptions();
    }, []);

    const columns = createStockMonitorColumns();
    const filterFields = useMemo(
        () => createStockMonitorFilterFields(filterOptions),
        [filterOptions],
    );

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
            const response = await axios.get('/api/stock-monitor', {
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
            <Helmet>
                <title>Stock Monitor</title>
            </Helmet>
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
