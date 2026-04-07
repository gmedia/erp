'use client';

import { Badge } from '@/components/ui/badge';
import { createSortingHeader } from '@/utils/columns';
import type { ColumnDef } from '@tanstack/react-table';
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

export function createReportSummaryColumn<TData>(
    options: SummaryColumnOptions<TData>,
): ColumnDef<TData> {
    const column: ColumnDef<TData> = {
        ...(options.id ? { id: options.id } : {}),
        ...(options.accessorKey ? { accessorKey: options.accessorKey } : {}),
        header: options.header,
        cell: ({ row }) => (
            <SummaryCell
                primary={options.getPrimary(row.original)}
                secondary={options.getSecondary(row.original)}
            />
        ),
    };

    if (!options.sortable) {
        return column;
    }

    return {
        ...column,
        ...createSortingHeader(options.header),
    };
}

export function createReportTextColumn<TData>(
    options: TextColumnOptions<TData>,
): ColumnDef<TData> {
    const column: ColumnDef<TData> = {
        ...(options.id ? { id: options.id } : {}),
        ...(options.accessorKey ? { accessorKey: options.accessorKey } : {}),
        header: options.header,
        cell: ({ row }) => (
            <TextCell
                value={options.getValue(row.original)}
                className={options.className}
            />
        ),
    };

    if (!options.sortable) {
        return column;
    }

    return {
        ...column,
        ...createSortingHeader(options.header),
    };
}

export function createReportWarehouseColumn<TData>(
    options: WarehouseColumnOptions<TData>,
): ColumnDef<TData> {
    const column: ColumnDef<TData> = {
        ...(options.id ? { id: options.id } : {}),
        ...(options.accessorKey ? { accessorKey: options.accessorKey } : {}),
        header: options.header,
        cell: ({ row }) => (
            <WarehouseSummaryCell
                warehouse={options.getWarehouse(row.original)}
            />
        ),
    };

    if (!options.sortable) {
        return column;
    }

    return {
        ...column,
        ...createSortingHeader(options.header),
    };
}

export function createReportStatusBadgeColumn<TData>(
    options: StatusBadgeColumnOptions<TData>,
): ColumnDef<TData> {
    return {
        accessorKey: options.accessorKey,
        ...createSortingHeader(options.header),
        cell: ({ row }) => (
            <StatusBadgeCell
                value={options.getValue(row.original)}
                variant={options.getVariant?.(row.original)}
            />
        ),
    };
}
