'use client';

import { DataTable } from '@/components/common/DataTableCore';
import { PageHeader } from '@/components/common/PageHeader';
import type { FieldDescriptor } from '@/components/common/filters';
import { useTranslation } from '@/contexts/i18n-context';
import type { FilterState } from '@/hooks/useCrudFilters';
import { useCrudFilters } from '@/hooks/useCrudFilters';
import { useCrudQuery } from '@/hooks/useCrudQuery';
import AppLayout from '@/layouts/app-layout';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import { formatCurrencyByRegionalSettings } from '@/utils/number-format';
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
    descriptionKey?: string;
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
    /** Kunci i18n untuk pesan empty state saat `requiredFilters` belum lengkap. */
    requiredFilterHintKey?: string;
    /** Kolom sekunder yang disembunyikan default (aktif via menu Columns). */
    defaultColumnVisibility?: Record<string, boolean>;
    summaryColumns?: readonly ReportSummaryColumn[];
};

export type ReportSummaryColumn = {
    key: string;
    label: string;
};

function formatSummaryAmount(value: string | undefined): string {
    if (value === undefined || value === '') {
        return '-';
    }

    return formatCurrencyByRegionalSettings(value);
}

export function ReportPageDescription({
    descriptionKey,
}: Readonly<{ descriptionKey: string }>) {
    const { t } = useTranslation();

    return <>{t(descriptionKey)}</>;
}

function ReportSummaryFooter({
    summary,
    columns,
}: Readonly<{
    summary: { as_of_date?: string; totals?: Record<string, string> };
    columns: readonly ReportSummaryColumn[];
}>) {
    const { t } = useTranslation();

    return (
        <div
            data-testid="report-summary"
            className="flex flex-wrap items-center justify-between gap-2 border-t bg-muted/40 px-4 py-2 text-sm"
        >
            <div className="flex flex-col">
                <span className="font-medium text-foreground">
                    {t('reports.totalRow')}
                </span>
                {summary.as_of_date ? (
                    <span
                        data-testid="report-as-of"
                        className="text-xs text-muted-foreground"
                    >
                        {t('reports.asOfDate').replace(
                            '{date}',
                            formatDateByRegionalSettings(summary.as_of_date),
                        )}
                    </span>
                ) : null}
            </div>
            <dl className="flex flex-wrap gap-x-6 gap-y-2">
                {columns.map((column) => (
                    <div key={column.key} className="flex flex-col items-end">
                        <dt className="text-xs text-muted-foreground">
                            {column.label}
                        </dt>
                        <dd
                            data-testid={`report-total-${column.key}`}
                            className="font-medium text-foreground tabular-nums"
                        >
                            {formatSummaryAmount(summary.totals?.[column.key])}
                        </dd>
                    </div>
                ))}
            </dl>
        </div>
    );
}

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

function ReportSelectFilterPrompt({
    hintKey,
    entityName,
}: Readonly<{ hintKey?: string; entityName: string }>) {
    const { t } = useTranslation();
    const hint = hintKey
        ? t(hintKey)
        : t('reports.selectFilterDefault').replace('{entity}', entityName);

    return (
        <div
            data-testid="report-filter-prompt"
            className="sticky left-0 flex w-[min(100%,calc(100vw_-_3rem))] flex-col items-center justify-center gap-2 rounded-lg border border-dashed px-4 py-16 text-center"
        >
            <Filter className="size-8 text-muted-foreground" aria-hidden />
            <p className="text-sm font-medium text-foreground">
                {t('reports.selectFilterTitle')}
            </p>
            <p className="max-w-md text-sm break-words text-muted-foreground">
                {hint}
            </p>
        </div>
    );
}

export function ReportDataTablePage<
    TData,
    TFilters extends FilterState = FilterState,
>({
    title,
    description,
    descriptionKey,
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
    requiredFilterHintKey,
    defaultColumnVisibility,
    summaryColumns,
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

    const { data, isLoading, meta, summary } = useCrudQuery<TData>({
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
                    <PageHeader
                        title={title}
                        description={
                            descriptionKey ? (
                                <ReportPageDescription
                                    descriptionKey={descriptionKey}
                                />
                            ) : (
                                description
                            )
                        }
                    />
                    {ready ? null : (
                        <ReportSelectFilterPrompt
                            hintKey={requiredFilterHintKey}
                            entityName={entityName}
                        />
                    )}
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
                            controlsDisabled={!ready}
                            entityName={entityName}
                            onView={onView}
                            defaultColumnVisibility={defaultColumnVisibility}
                            emptyState={ready ? undefined : ''}
                            footer={
                                ready && summaryColumns && summary ? (
                                    <ReportSummaryFooter
                                        summary={summary}
                                        columns={summaryColumns}
                                    />
                                ) : undefined
                            }
                        />
                    </div>
                </div>
                {children}
            </AppLayout>
        </>
    );
}
