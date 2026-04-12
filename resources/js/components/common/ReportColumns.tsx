'use client';

import { Badge } from '@/components/ui/badge';
import { createSortingHeader } from '@/utils/columns';
import type { CellContext, ColumnDef } from '@tanstack/react-table';
import type { ComponentProps, ReactNode } from 'react';

type DisplayValue = ReactNode | null | undefined;

type WarehouseSummary =
    | {
          name: string | null;
          code?: string | null;
          branch?: { name: string | null } | null;
      }
    | null
    | undefined;

type SummaryColumnOptions<TData> = {
    id?: string;
    accessorKey?: string;
    header: string;
    getPrimary: (row: TData) => DisplayValue;
    getSecondary: (row: TData) => DisplayValue;
    sortable?: boolean;
};

type TextColumnOptions<TData> = {
    id?: string;
    accessorKey?: string;
    header: string;
    getValue: (row: TData) => DisplayValue;
    className?: string;
    sortable?: boolean;
};

type WarehouseColumnOptions<TData> = {
    id?: string;
    accessorKey?: string;
    header: string;
    getWarehouse: (row: TData) => WarehouseSummary;
    sortable?: boolean;
};

type StatusBadgeColumnOptions<TData> = {
    accessorKey: string;
    header: string;
    getValue: (row: TData) => string | null | undefined;
    getVariant?: (
        row: TData,
    ) => ComponentProps<typeof Badge>['variant'] | undefined;
};

type ReportDisplayColumnOptions<TData> = {
    id?: string;
    accessorKey?: string;
    header: string;
    sortable?: boolean;
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
}: {
    primary: DisplayValue;
    secondary: DisplayValue;
}) {
    return (
        <div className="space-y-0.5">
            <div className="font-medium">{resolveDisplayValue(primary)}</div>
            <div className="text-xs text-muted-foreground">
                {resolveDisplayValue(secondary)}
            </div>
        </div>
    );
}

export function TextCell({
    value,
    className,
}: {
    value: DisplayValue;
    className?: string;
}) {
    return <div className={className}>{resolveDisplayValue(value)}</div>;
}

export function WarehouseSummaryCell({
    warehouse,
}: {
    warehouse: WarehouseSummary;
}) {
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
    variant = 'outline',
}: {
    value: string | null | undefined;
    variant?: ComponentProps<typeof Badge>['variant'];
}) {
    return (
        <Badge variant={variant} className="capitalize">
            {formatReportLabel(value)}
        </Badge>
    );
}

function resolveReportColumnId(header: string, id?: string): string {
    return id ?? header.toLowerCase().replaceAll(/\s+/g, '_');
}

function createReportDisplayColumn<TData>({
    id,
    accessorKey,
    header,
    sortable,
    renderCell,
}: ReportDisplayColumnOptions<TData>): ColumnDef<TData> {
    const cell = ({ row }: CellContext<TData, unknown>) =>
        renderCell(row.original);
    const columnId = resolveReportColumnId(header, id);

    if (sortable && accessorKey) {
        return {
            id: columnId,
            accessorKey,
            cell,
            ...createSortingHeader(header),
        };
    }

    if (accessorKey) {
        return {
            id: columnId,
            accessorKey,
            enableSorting: false,
            header,
            cell,
        };
    }

    return {
        id: columnId,
        enableSorting: false,
        header,
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
            />
        ),
    });
}

export function createReportTextColumn<TData>(
    options: TextColumnOptions<TData>,
): ColumnDef<TData> {
    return createReportDisplayColumn({
        id: options.id,
        accessorKey: options.accessorKey,
        header: options.header,
        sortable: options.sortable,
        renderCell: (row) => (
            <TextCell
                value={options.getValue(row)}
                className={options.className}
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
    return {
        accessorKey: options.accessorKey,
        ...createSortingHeader(options.header),
        cell: ({ row }: CellContext<TData, unknown>) => (
            <StatusBadgeCell
                value={options.getValue(row.original)}
                variant={options.getVariant?.(row.original)}
            />
        ),
    };
}
