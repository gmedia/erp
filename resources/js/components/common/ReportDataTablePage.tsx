'use client';

import { DataTable } from '@/components/common/DataTableCore';
import { PageHeader } from '@/components/common/PageHeader';
import type { FieldDescriptor } from '@/components/common/filters';
import type { FilterState } from '@/hooks/useCrudFilters';
import { useCrudFilters } from '@/hooks/useCrudFilters';
import { useCrudQuery } from '@/hooks/useCrudQuery';
import AppLayout from '@/layouts/app-layout';
import type { ColumnDef } from '@tanstack/react-table';
import { Filter } from 'lucide-react';
import type { ReactNode } from 'react';
import { Helmet } from 'react-helmet-async';

type BreadcrumbItem = {
    title: string;
    href: string;
};

export function createReportBreadcrumbs(
    title: string,
    href: string,
): BreadcrumbItem[] {
    return [
        { title: 'Reports', href: '#' },
        { title, href },
    ];
}

export function createEmptyReportFilters<const TKeys extends readonly string[]>(
    fields: TKeys,
): Record<TKeys[number] | 'search', string> {
    return Object.fromEntries(
        ['search', ...fields].map((field) => [field, '']),
    ) as Record<TKeys[number] | 'search', string>;
}

type ReportDataTablePageProps<
    TData,
    TFilters extends FilterState = FilterState,
> = {
    title: string;
    description?: string;
    breadcrumbs: BreadcrumbItem[];
    columns: ColumnDef<TData>[];
    filterFields: FieldDescriptor[];
    initialFilters: TFilters;
    endpoint: string;
    queryKey: string[];
    entityName: string;
    exportEndpoint: string;
    onView?: (item: TData) => void;
    children?: ReactNode;
    /**
     * Filter yang wajib dipilih sebelum query dijalankan (mis. `budget_id`,
     * `account_id`). Selama kosong, halaman menampilkan empty state
     * instruktif dan tidak memanggil API (W4-19/W4-22).
     */
    requiredFilters?: readonly (keyof TFilters & string)[];
    /** Pesan empty state saat `requiredFilters` belum lengkap. */
    requiredFilterHint?: string;
    /** Kolom sekunder yang disembunyikan default (aktif via menu Columns). */
    defaultColumnVisibility?: Record<string, boolean>;
};

function hasAllRequiredFilters(
    filters: Record<string, unknown>,
    required: readonly string[] | undefined,
): boolean {
    if (!required || required.length === 0) {
        return true;
    }

    return required.every((key) => {
        const value = filters[key];
        return value !== undefined && value !== null && String(value) !== '';
    });
}

function ReportSelectFilterPrompt({ hint }: Readonly<{ hint: string }>) {
    return (
        <div
            data-testid="report-filter-prompt"
            className="flex flex-col items-center justify-center gap-2 rounded-lg border border-dashed px-6 py-16 text-center"
        >
            <Filter className="size-8 text-muted-foreground" aria-hidden />
            <p className="text-sm font-medium text-foreground">
                Pilih filter untuk menampilkan laporan
            </p>
            <p className="max-w-md text-sm text-muted-foreground">{hint}</p>
        </div>
    );
}

export function ReportDataTablePage<
    TData,
    TFilters extends FilterState = FilterState,
>({
    title,
    description,
    breadcrumbs,
    columns,
    filterFields,
    initialFilters,
    endpoint,
    queryKey,
    entityName,
    exportEndpoint,
    onView,
    children,
    requiredFilters,
    requiredFilterHint,
    defaultColumnVisibility,
}: Readonly<ReportDataTablePageProps<TData, TFilters>>) {
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

    const ready = hasAllRequiredFilters(
        filters as Record<string, unknown>,
        requiredFilters,
    );

    const { data, isLoading, meta } = useCrudQuery<TData>({
        endpoint,
        queryKey,
        entityName,
        pagination,
        filters,
        enabled: ready,
    });

    return (
        <>
            <Helmet>
                <title>{title}</title>
            </Helmet>
            <AppLayout breadcrumbs={breadcrumbs}>
                <div className="flex w-full flex-col gap-3 overflow-x-auto p-4">
                    <PageHeader title={title} description={description} />
                    <div>
                        <DataTable
                            columns={columns}
                            data={ready ? data : []}
                            pagination={{
                                page: meta.current_page,
                                per_page: meta.per_page,
                                total: ready ? meta.total : 0,
                                last_page: meta.last_page,
                                from: ready ? (meta.from ?? 0) : 0,
                                to: ready ? (meta.to ?? 0) : 0,
                            }}
                            onPageChange={handlePageChange}
                            onPageSizeChange={handlePageSizeChange}
                            onSearchChange={handleSearchChange}
                            isLoading={isLoading}
                            filterValue={String(filters.search ?? '')}
                            filters={filters}
                            onFilterChange={(newFilters) =>
                                handleFilterChange(
                                    newFilters as Partial<TFilters>,
                                )
                            }
                            onResetFilters={resetFilters}
                            filterFields={filterFields}
                            exportEndpoint={exportEndpoint}
                            entityName={entityName}
                            onView={onView}
                            defaultColumnVisibility={defaultColumnVisibility}
                            emptyState={
                                ready ? undefined : (
                                    <ReportSelectFilterPrompt
                                        hint={
                                            requiredFilterHint ??
                                            `Pilih filter yang diperlukan untuk menampilkan ${entityName}.`
                                        }
                                    />
                                )
                            }
                        />
                    </div>
                </div>
                {children}
            </AppLayout>
        </>
    );
}
