'use client';

import { DataTable } from '@/components/common/DataTableCore';
import type { FieldDescriptor } from '@/components/common/filters';
import type { FilterState } from '@/hooks/useCrudFilters';
import { useCrudFilters } from '@/hooks/useCrudFilters';
import { useCrudQuery } from '@/hooks/useCrudQuery';
import AppLayout from '@/layouts/app-layout';
import type { ColumnDef } from '@tanstack/react-table';
import type { ReactNode } from 'react';
import { useState } from 'react';
import { Helmet } from 'react-helmet-async';

type BreadcrumbItem = {
    title: string;
    href: string;
};

type AuditTrailPageProps<
    TItem,
    TFilters extends FilterState = FilterState,
> = {
    title: string;
    breadcrumbs: BreadcrumbItem[];
    filterFields: FieldDescriptor[];
    initialFilters: TFilters;
    endpoint: string;
    queryKey: string[];
    entityName: string;
    exportEndpoint: string;
    buildColumns: (options: { onViewDetail: (item: TItem) => void }) => ColumnDef<TItem>[];
    renderDetailModal: (options: {
        item: TItem | null;
        open: boolean;
        onOpenChange: (open: boolean) => void;
    }) => ReactNode;
};

export function AuditTrailPage<
    TItem,
    TFilters extends FilterState = FilterState,
>({
    title,
    breadcrumbs,
    filterFields,
    initialFilters,
    endpoint,
    queryKey,
    entityName,
    exportEndpoint,
    buildColumns,
    renderDetailModal,
}: Readonly<AuditTrailPageProps<TItem, TFilters>>) {
    const [selectedItem, setSelectedItem] = useState<TItem | null>(null);
    const [isDetailModalOpen, setIsDetailModalOpen] = useState(false);

    const handleViewDetail = (item: TItem) => {
        setSelectedItem(item);
        setIsDetailModalOpen(true);
    };

    const columns = buildColumns({ onViewDetail: handleViewDetail });

    const {
        filters,
        pagination,
        handleFilterChange,
        handleSearchChange,
        handlePageChange,
        handlePageSizeChange,
        resetFilters,
    } = useCrudFilters<TFilters>({
        initialFilters,
    });

    const { data, isLoading, meta } = useCrudQuery<TItem>({
        endpoint,
        queryKey,
        entityName,
        pagination,
        filters,
    });

    return (
        <>
            <Helmet>
                <title>{title}</title>
            </Helmet>
            <AppLayout breadcrumbs={breadcrumbs}>
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
                            onPageSizeChange={handlePageSizeChange}
                            onSearchChange={handleSearchChange}
                            isLoading={isLoading}
                            filterValue={filters.search}
                            filters={filters}
                            onFilterChange={handleFilterChange}
                            onResetFilters={resetFilters}
                            filterFields={filterFields}
                            exportEndpoint={exportEndpoint}
                            entityName={entityName}
                        />
                    </div>
                </div>

                {renderDetailModal({
                    item: selectedItem,
                    open: isDetailModalOpen,
                    onOpenChange: setIsDetailModalOpen,
                })}
            </AppLayout>
        </>
    );
}