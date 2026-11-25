'use client';

import { Badge } from '@/components/ui/badge';
import { formatStatusLabel, statusBadgeVariant } from '@/lib/status-badge';
import {
    createActionsColumn,
    createCurrencyColumn,
    createSelectColumn,
    createSortingHeader,
    createTextColumn,
} from '@/utils/columns';
import { ColumnDef } from '@tanstack/react-table';

import { Product } from '@/types/entity';

/**
 * Cell renderer for category column
 */
const renderCategoryCell = ({ row }: { row: { original: Product } }) => {
    const val = row.original.category;
    return <div>{typeof val === 'object' ? val.name : val}</div>;
};

/**
 * Cell renderer for type column with badge
 */
const renderTypeCell = ({ row }: { row: { original: Product } }) => {
    const type = row.original.type;
    const labels: Record<string, string> = {
        raw_material: 'Raw Material',
        work_in_progress: 'WIP',
        finished_good: 'Finished Good',
        purchased_good: 'Purchased Good',
        service: 'Service',
    };

    return <Badge variant="secondary">{labels[type] || type}</Badge>;
};

/**
 * Cell renderer for status column with badge
 */
const renderStatusCell = ({ row }: { row: { original: Product } }) => {
    const status = row.original.status;
    return (
        <Badge variant={statusBadgeVariant(status)}>
            {formatStatusLabel(status)}
        </Badge>
    );
};

export const productColumns: ColumnDef<Product>[] = [
    createSelectColumn(),
    createTextColumn<Product>({
        accessorKey: 'code',
        label: { key: 'column.code', fallback: 'Code' },
    }),
    createTextColumn<Product>({
        accessorKey: 'name',
        label: { key: 'column.name', fallback: 'Name' },
        primary: true,
    }),
    {
        accessorKey: 'type',
        ...createSortingHeader({ key: 'column.type', fallback: 'Type' }),
        cell: renderTypeCell,
    },
    {
        accessorKey: 'category',
        ...createSortingHeader({
            key: 'column.category',
            fallback: 'Category',
        }),
        cell: renderCategoryCell,
    },
    createCurrencyColumn<Product>({
        accessorKey: 'cost',
        label: { key: 'column.cost', fallback: 'Cost' },
        currency: 'IDR',
        locale: 'id-ID',
    }),
    createCurrencyColumn<Product>({
        accessorKey: 'selling_price',
        label: { key: 'column.price', fallback: 'Price' },
        currency: 'IDR',
        locale: 'id-ID',
    }),
    {
        accessorKey: 'status',
        ...createSortingHeader({ key: 'column.status', fallback: 'Status' }),
        cell: renderStatusCell,
    },
    createActionsColumn<Product>(),
];
