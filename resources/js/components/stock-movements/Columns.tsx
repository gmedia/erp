'use client';

import {
    SummaryCell,
    TextCell,
    WarehouseSummaryCell,
} from '@/components/common/ReportColumns';
import { createNumberColumn, createSortingHeader } from '@/utils/columns';
import { formatDateTimeByRegionalSettings } from '@/utils/date-format';
import type { ColumnDef } from '@tanstack/react-table';

export type StockMovementItem = {
    id: number;
    product: { id: number; code: string | null; name: string } | null;
    warehouse: {
        id: number;
        code: string | null;
        name: string;
        branch: { id: number; name: string } | null;
    } | null;
    movement_type: string;
    quantity_in: string;
    quantity_out: string;
    balance_after: string;
    unit_cost: string | null;
    average_cost_after: string | null;
    reference_number: string | null;
    moved_at: string | null;
    notes: string | null;
    created_by: { id: number; name: string; email: string } | null;
};

function formatMovementType(value: string | null | undefined): string {
    if (!value) return '-';
    return value
        .split('_')
        .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
        .join(' ');
}

function formatDateTime(value: string | null | undefined): string {
    return formatDateTimeByRegionalSettings(value);
}

function getReferenceUrl(referenceNumber: string | null): string | undefined {
    if (!referenceNumber) return undefined;

    const ref = referenceNumber.toUpperCase();

    if (ref.startsWith('ST-')) {
        return `/stock-transfers?search=${encodeURIComponent(referenceNumber)}`;
    }

    if (ref.startsWith('SA-')) {
        return `/stock-adjustments?search=${encodeURIComponent(referenceNumber)}`;
    }

    if (ref.startsWith('SO-')) {
        return `/inventory-stocktakes?search=${encodeURIComponent(referenceNumber)}`;
    }

    return undefined;
}

export function createStockMovementsColumns(): ColumnDef<StockMovementItem>[] {
    return [
        {
            id: 'moved_at',
            accessorFn: (row) => row.moved_at,
            ...createSortingHeader('Moved At'),
            cell: ({ row }) => (
                <TextCell value={formatDateTime(row.original.moved_at)} />
            ),
        },
        {
            id: 'product_name',
            header: 'Product',
            cell: ({ row }) => (
                <SummaryCell
                    primary={row.original.product?.name}
                    secondary={row.original.product?.code}
                />
            ),
        },
        {
            id: 'warehouse_name',
            header: 'Warehouse',
            cell: ({ row }) => (
                <WarehouseSummaryCell warehouse={row.original.warehouse} />
            ),
        },
        {
            accessorKey: 'movement_type',
            ...createSortingHeader('Type'),
            cell: ({ row }) => (
                <div>{formatMovementType(row.original.movement_type)}</div>
            ),
        },
        {
            accessorKey: 'reference_number',
            ...createSortingHeader('Reference'),
            cell: ({ row }) => {
                const ref = row.original.reference_number;
                const url = getReferenceUrl(ref);

                if (!ref) return <div>-</div>;

                if (!url) return <div>{ref}</div>;

                return (
                    <a href={url} className="text-blue-600 hover:underline">
                        {ref}
                    </a>
                );
            },
        },
        createNumberColumn<StockMovementItem>({
            accessorKey: 'quantity_in',
            label: 'Qty In',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }),
        createNumberColumn<StockMovementItem>({
            accessorKey: 'quantity_out',
            label: 'Qty Out',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }),
        createNumberColumn<StockMovementItem>({
            accessorKey: 'balance_after',
            label: 'Balance',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }),
        createNumberColumn<StockMovementItem>({
            accessorKey: 'unit_cost',
            label: 'Unit Cost',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }),
        createNumberColumn<StockMovementItem>({
            accessorKey: 'average_cost_after',
            label: 'Avg Cost',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }),
        {
            id: 'created_by',
            header: 'Created By',
            cell: ({ row }) => (
                <TextCell value={row.original.created_by?.name} />
            ),
        },
    ];
}
