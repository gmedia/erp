'use client';

import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { DataTable } from '@/components/common/DataTableCore';
import { useCrudFilters } from '@/hooks/useCrudFilters';
import { useCrudQuery } from '@/hooks/useCrudQuery';
import { assetColumns } from '@/components/assets/AssetColumns';
import { createAssetFilterFields } from '@/components/assets/AssetFilters';
import { AssetViewModal } from '@/components/assets/AssetViewModal';
import { useState } from 'react';
import { Asset } from '@/types/asset';

export default function AssetRegisterReport() {
    // Generate filters
    const filterFields = createAssetFilterFields();

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
            asset_category_id: '',
            branch_id: '',
            status: '',
            condition: '',
        },
    });

    const { data, isLoading, meta } = useCrudQuery<Asset>({
        endpoint: '/api/assets', // Reusing the main assets endpoint, or we can use reports/assets/register if we return json
        queryKey: ['assets-report'],
        entityName: 'Asset Report',
        pagination,
        filters,
    });

    const [isViewModalOpen, setIsViewModalOpen] = useState(false);
    const [viewItem, setViewItem] = useState<Asset | null>(null);

    const handleView = (item: Asset) => {
        setViewItem(item);
        setIsViewModalOpen(true);
    };

    // Remove the select column and action column for the report if needed, or keep them.
    // In this case, we keep actions so they can view details, but we remove the select checkboxes.
    const reportColumns = assetColumns.filter((col) => col.id !== 'select');

    return (
        <>
            <Head title="Asset Register Report" />
            <AppLayout breadcrumbs={[{ title: 'Reports', href: '#' }, { title: 'Asset Register', href: '/reports/assets/register' }]}>
                <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                    <div className="rounded-lg bg-white">
                        <DataTable
                            columns={reportColumns}
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
                            exportEndpoint="/reports/assets/register/export"
                            entityName="Asset"
                            onView={handleView}
                        />
                    </div>
                </div>

                <AssetViewModal
                    open={isViewModalOpen}
                    onClose={() => {
                        setIsViewModalOpen(false);
                        setViewItem(null);
                    }}
                    item={viewItem}
                />
            </AppLayout>
        </>
    );
}
