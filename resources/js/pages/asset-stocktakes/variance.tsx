'use client';

import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { DataTable } from '@/components/common/DataTableCore';
import { useCrudFilters } from '@/hooks/useCrudFilters';
import { useCrudQuery } from '@/hooks/useCrudQuery';
import { varianceColumns, AssetStocktakeVarianceItem } from '@/components/asset-stocktakes/variance/Columns';
import { createVarianceFilterFields } from '@/components/asset-stocktakes/variance/Filters';

export default function StocktakeVarianceReport() {
    const filterFields = createVarianceFilterFields();

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
            asset_stocktake_id: '',
            branch_id: '',
            result: '',
        },
    });

    const { data, isLoading, meta } = useCrudQuery<AssetStocktakeVarianceItem>({
        endpoint: '/api/asset-stocktake-variances',
        queryKey: ['asset-stocktake-variances'],
        entityName: 'Stocktake Variance',
        pagination,
        filters,
    });

    return (
        <>
            <Head title="Stocktake Variance Report" />
            <AppLayout breadcrumbs={[{ title: 'Reports', href: '#' }, { title: 'Stocktake Variance', href: '/asset-stocktake-variances' }]}>
                <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                    <div className="rounded-lg bg-white shadow-sm border">
                        <DataTable
                            columns={varianceColumns}
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
                            onPageSizeChange={(per_page) => handlePageSizeChange(per_page)}
                            onSearchChange={handleSearchChange}
                            isLoading={isLoading}
                            filterValue={filters.search}
                            filters={filters}
                            onFilterChange={handleFilterChange}
                            onResetFilters={resetFilters}
                            filterFields={filterFields}
                            exportEndpoint="/api/asset-stocktake-variances/export"
                            entityName="Variance"
                        />
                    </div>
                </div>
            </AppLayout>
        </>
    );
}
