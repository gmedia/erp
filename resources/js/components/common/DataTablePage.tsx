'use client';

import { DataTable } from '@/components/common/DataTableCore';
import type { FieldDescriptor } from '@/components/common/filters';
import type { FilterState } from '@/hooks/useCrudFilters';
import AppLayout from '@/layouts/app-layout';
import type { ColumnDef } from '@tanstack/react-table';
import type { ReactNode } from 'react';
import { Helmet } from 'react-helmet-async';

type BreadcrumbItem = {
    title: string;
    href: string;
};

type PaginationMeta = {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
    from?: number;
    to?: number;
};

type DataTablePageProps<TData, TFilters extends FilterState = FilterState> = {
    title: string;
    breadcrumbs: BreadcrumbItem[];
    columns: ColumnDef<TData>[];
    data: TData[];
    meta: PaginationMeta;
    isLoading: boolean;
    filterValue: string;
    filters: TFilters;
    filterFields: FieldDescriptor[];
    exportEndpoint: string;
    entityName: string;
    onPageChange: (page: number) => void;
    onPageSizeChange: (perPage: number) => void;
    onSearchChange: (value: string) => void;
    onFilterChange: (filters: Partial<TFilters>) => void;
    onResetFilters: () => void;
    children?: ReactNode;
};

export function DataTablePage<
    TData,
    TFilters extends FilterState = FilterState,
>({
    title,
    breadcrumbs,
    columns,
    data,
    meta,
    isLoading,
    filterValue,
    filters,
    filterFields,
    exportEndpoint,
    entityName,
    onPageChange,
    onPageSizeChange,
    onSearchChange,
    onFilterChange,
    onResetFilters,
    children,
}: Readonly<DataTablePageProps<TData, TFilters>>) {
    const tableFilters = filters as Record<string, string | number | undefined>;

    return (
        <>
            <Helmet>
                <title>{title}</title>
            </Helmet>
            <AppLayout breadcrumbs={breadcrumbs}>
                <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                    {children}
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
                            onPageChange={onPageChange}
                            onPageSizeChange={onPageSizeChange}
                            onSearchChange={onSearchChange}
                            isLoading={isLoading}
                            filterValue={filterValue}
                            filters={tableFilters}
                            onFilterChange={(newFilters) =>
                                onFilterChange(newFilters as Partial<TFilters>)
                            }
                            onResetFilters={onResetFilters}
                            filterFields={filterFields}
                            exportEndpoint={exportEndpoint}
                            entityName={entityName}
                        />
                    </div>
                </div>
            </AppLayout>
        </>
    );
}
