'use client';

import {
    SummaryCell,
    TextCell,
    WarehouseSummaryCell,
} from '@/components/common/ReportColumns';
import {
    createCurrencyColumn,
    createNumberColumn,
    createSortingHeader,
} from '@/utils/columns';
import { formatDateTimeByRegionalSettings } from '@/utils/date-format';
import type { ColumnDef } from '@tanstack/react-table';

export type InventoryValuationItem = {
    id: number;
    product: {
        id: number;
        code: string | null;
        name: string;
        category: { id: number; name: string } | null;
        unit: { id: number; name: string } | null;
    } | null;
    warehouse: {
        id: number;
        code: string | null;
        name: string;
        branch: { id: number; name: string } | null;
    } | null;
    quantity_on_hand: string;
    average_cost: string;
    stock_value: string;
    moved_at: string | null;
};

function formatDate(value: string | null | undefined): string {
    return formatDateTimeByRegionalSettings(value);
}

export const inventoryValuationColumns: ColumnDef<InventoryValuationItem>[] = [
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
        id: 'category_name',
        header: 'Category',
        cell: ({ row }) => <TextCell value={row.original.product?.category?.name} />,
    },
    {
        id: 'unit_name',
        header: 'Unit',
        cell: ({ row }) => <TextCell value={row.original.product?.unit?.name} />,
    },
    {
        id: 'warehouse_name',
        header: 'Warehouse',
        cell: ({ row }) => (
            <WarehouseSummaryCell warehouse={row.original.warehouse} />
        ),
    },
    createNumberColumn<InventoryValuationItem>({
        accessorKey: 'quantity_on_hand',
        label: 'Qty On Hand',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }),
    createCurrencyColumn<InventoryValuationItem>({
        accessorKey: 'average_cost',
        label: 'Avg Cost',
        currency: 'IDR',
        locale: 'id-ID',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
        className: 'text-right',
    }),
    createCurrencyColumn<InventoryValuationItem>({
        accessorKey: 'stock_value',
        label: 'Stock Value',
        currency: 'IDR',
        locale: 'id-ID',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
        className: 'text-right',
    }),
    {
        accessorKey: 'moved_at',
        ...createSortingHeader('Last Movement'),
        cell: ({ row }) => <TextCell value={formatDate(row.original.moved_at)} />,
    },
];
