'use client';

import {
    labelFallbackText,
    LabelText,
    type TranslatableLabel,
} from '@/components/common/TranslatedText';
import { Badge } from '@/components/ui/badge';
import { formatStatusLabel, statusBadgeVariant } from '@/lib/status-badge';
import { cn } from '@/lib/utils';
import { createSortingHeader } from '@/utils/columns';
import type { CellContext, ColumnDef } from '@tanstack/react-table';
import type { ComponentProps, ReactNode } from 'react';

type DisplayValue = ReactNode | null;

type WarehouseSummary = {
    name: string | null;
    code?: string | null;
    branch?: { name: string | null } | null;
} | null;

type SummaryColumnOptions<TData> = {
    id?: string;
    accessorKey?: string;
    header: TranslatableLabel;
    getPrimary: (row: TData) => DisplayValue;
    getSecondary: (row: TData) => DisplayValue;
    sortable?: boolean;
    className?: string;
};

type TextColumnOptions<TData> = {
    id?: string;
    accessorKey?: string;
    header: TranslatableLabel;
    getValue: (row: TData) => DisplayValue;
    className?: string;
    sortable?: boolean;
    align?: 'left' | 'right';
};

type WarehouseColumnOptions<TData> = {
    id?: string;
    accessorKey?: string;
    header: TranslatableLabel;
    getWarehouse: (row: TData) => WarehouseSummary;
    sortable?: boolean;
};

type StatusBadgeColumnOptions<TData> = {
    id?: string;
    accessorKey: string;
    header: TranslatableLabel;
    getValue: (row: TData) => string | null | undefined;
    getVariant?: (
        row: TData,
    ) => ComponentProps<typeof Badge>['variant'] | undefined;
};

type ReportDisplayColumnOptions<TData> = {
    id?: string;
    accessorKey?: string;
    header: TranslatableLabel;
    sortable?: boolean;
    align?: 'left' | 'right';
    renderCell: (row: TData) => ReactNode;
};

function resolveDisplayValue(value: DisplayValue): ReactNode {
    if (value === null || value === undefined || value === '') {
        return '-';
    }

    return value;
}

export function formatReportLabel(value: string | null | undefined): string {
    if (!value) return '-';
    return value.replaceAll('_', ' ');
}

export function formatWarehouseCodeLabel(
    code: string | null | undefined,
    branchName: string | null | undefined,
): string {
    return (code ?? '-') + (branchName ? ` • ${branchName}` : '');
}

export function SummaryCell({
    primary,
    secondary,
    className,
}: Readonly<{
    primary: DisplayValue;
    secondary: DisplayValue;
    className?: string;
}>) {
    return (
        <div className={cn('max-w-[16rem] space-y-0.5', className)}>
            <TruncatedText className="font-medium" value={primary} />
            <TruncatedText
                className="text-xs text-muted-foreground"
                value={secondary}
            />
        </div>
    );
}

function TruncatedText({
    value,
    className,
}: Readonly<{
    value: DisplayValue;
    className?: string;
}>) {
    const display = resolveDisplayValue(value);
    const title = typeof display === 'string' ? display : undefined;
    return (
        <div className={cn('truncate', className)} title={title}>
            {display}
        </div>
    );
}

export function TextCell({
    value,
    className,
}: Readonly<{
    value: DisplayValue;
    className?: string;
}>) {
    return (
        <TruncatedText
            className={cn('max-w-[12rem]', className)}
            value={value}
        />
    );
}

export function WarehouseSummaryCell({
    warehouse,
}: Readonly<{
    warehouse: WarehouseSummary;
}>) {
    return (
        <SummaryCell
            primary={warehouse?.name}
            secondary={formatWarehouseCodeLabel(
                warehouse?.code,
                warehouse?.branch?.name,
            )}
        />
    );
}

export function StatusBadgeCell({
    value,
    variant,
}: Readonly<{
    value: string | null;
    variant?: ComponentProps<typeof Badge>['variant'];
}>) {
    // Tone default dari peta status→tone terpusat (W4-01/W4-02);
    // pemakai lama tetap bisa override lewat `getVariant`.
    const resolvedVariant = variant ?? statusBadgeVariant(value);
    return <Badge variant={resolvedVariant}>{formatStatusLabel(value)}</Badge>;
}

function resolveReportColumnId({
    id,
    accessorKey,
    header,
}: {
    id?: string;
    accessorKey?: string;
    header: TranslatableLabel;
}): string {
    if (id) {
        return id;
    }

    if (accessorKey) {
        return accessorKey.replaceAll('.', '_');
    }

    return labelFallbackText(header).toLowerCase().replaceAll(/\s+/g, '_');
}

function createReportDisplayColumn<TData>({
    id,
    accessorKey,
    header,
    sortable,
    align = 'left',
    renderCell,
}: ReportDisplayColumnOptions<TData>): ColumnDef<TData> {
    const cell = ({ row }: CellContext<TData, unknown>) =>
        renderCell(row.original);
    const columnId = resolveReportColumnId({
        id,
        accessorKey,
        header,
    });

    if (sortable && accessorKey) {
        const sortingHeader = createSortingHeader(header);
        return {
            id: columnId,
            accessorKey,
            cell,
            meta: align === 'right' ? { numeric: true } : undefined,
            ...(align === 'right'
                ? {
                      header: (
                          context: Parameters<typeof sortingHeader.header>[0],
                      ) => (
                          <div className="flex justify-end [&_button]:mr-0 [&_button]:ml-auto">
                              {sortingHeader.header(context)}
                          </div>
                      ),
                  }
                : sortingHeader),
        };
    }

    const plainHeader = () => <LabelText label={header} />;
    const alignedHeader =
        align === 'right'
            ? () => (
                  <div className="text-right">
                      <LabelText label={header} />
                  </div>
              )
            : plainHeader;

    if (accessorKey) {
        return {
            id: columnId,
            accessorKey,
            enableSorting: false,
            meta: align === 'right' ? { numeric: true } : undefined,
            header: alignedHeader,
            cell,
        };
    }

    return {
        id: columnId,
        meta: align === 'right' ? { numeric: true } : undefined,
        enableSorting: false,
        header: alignedHeader,
        cell,
    };
}

export function createReportSummaryColumn<TData>(
    options: SummaryColumnOptions<TData>,
): ColumnDef<TData> {
    return createReportDisplayColumn({
        id: options.id,
        accessorKey: options.accessorKey,
        header: options.header,
        sortable: options.sortable,
        renderCell: (row) => (
            <SummaryCell
                primary={options.getPrimary(row)}
                secondary={options.getSecondary(row)}
                className={options.className}
            />
        ),
    });
}

export function createReportTextColumn<TData>(
    options: TextColumnOptions<TData>,
): ColumnDef<TData> {
    const align = options.align ?? 'left';
    return createReportDisplayColumn({
        id: options.id,
        accessorKey: options.accessorKey,
        header: options.header,
        sortable: options.sortable,
        align,
        renderCell: (row) => (
            <TextCell
                value={options.getValue(row)}
                className={cn(
                    options.className,
                    align === 'right' && 'text-right tabular-nums',
                )}
            />
        ),
    });
}

export function createReportWarehouseColumn<TData>(
    options: WarehouseColumnOptions<TData>,
): ColumnDef<TData> {
    return createReportDisplayColumn({
        id: options.id,
        accessorKey: options.accessorKey,
        header: options.header,
        sortable: options.sortable,
        renderCell: (row) => (
            <WarehouseSummaryCell warehouse={options.getWarehouse(row)} />
        ),
    });
}

export function createReportStatusBadgeColumn<TData>(
    options: StatusBadgeColumnOptions<TData>,
): ColumnDef<TData> {
    const columnId = resolveReportColumnId({
        id: options.id,
        accessorKey: options.accessorKey,
        header: options.header,
    });

    return {
        id: columnId,
        accessorKey: options.accessorKey,
        ...createSortingHeader(options.header),
        cell: ({ row }: CellContext<TData, unknown>) => (
            <StatusBadgeCell
                value={options.getValue(row.original) ?? null}
                variant={options.getVariant?.(row.original)}
            />
        ),
    };
}
