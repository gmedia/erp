'use client';

import { DataTable } from '@/components/common/DataTableCore';
import { PageHeader } from '@/components/common/PageHeader';
import {
    LabelText,
    labelFallbackText,
    type TranslatableLabel,
} from '@/components/common/TranslatedText';
import type { FieldDescriptor } from '@/components/common/filters';
import type { FilterState } from '@/hooks/useCrudFilters';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import type { ColumnDef } from '@tanstack/react-table';
import type { ReactNode } from 'react';
import { Helmet } from 'react-helmet-async';

type PaginationMeta = {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
    from?: number;
    to?: number;
};

type DataTablePageProps<TData, TFilters extends FilterState = FilterState> = {
    title: TranslatableLabel;
    description?: TranslatableLabel;
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
    description,
}: Readonly<DataTablePageProps<TData, TFilters>>) {
    const tableFilters = filters as Record<string, string | number | undefined>;
    const pageTitle = labelFallbackText(title);

    return (
        <>
            <Helmet>
                <title>{pageTitle}</title>
            </Helmet>
            <AppLayout breadcrumbs={breadcrumbs}>
                <div className="flex w-full flex-col gap-3 overflow-x-auto p-4">
                    <PageHeader
                        title={<LabelText label={title} />}
                        description={
                            description ? (
                                <LabelText label={description} />
                            ) : undefined
                        }
                    />
                    {children}
                    <div>
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
