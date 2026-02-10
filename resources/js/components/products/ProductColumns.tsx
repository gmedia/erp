'use client';

import { ColumnDef } from '@tanstack/react-table';
import { Badge } from '@/components/ui/badge';
import {
    createActionsColumn,
    createCurrencyColumn,
    createSelectColumn,
    createSortingHeader,
    createTextColumn,
} from '@/utils/columns';

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
    const variants: Record<string, 'default' | 'secondary' | 'outline' | 'destructive'> = {
        active: 'default',
        inactive: 'secondary',
        discontinued: 'destructive',
    };
    
    return (
        <Badge variant={variants[status] || 'outline'}>
            {status.charAt(0).toUpperCase() + status.slice(1)}
        </Badge>
    );
};

export const productColumns: ColumnDef<Product>[] = [
    createSelectColumn(),
    createTextColumn<Product>({ accessorKey: 'code', label: 'Code' }),
    createTextColumn<Product>({ accessorKey: 'name', label: 'Name' }),
    {
        accessorKey: 'type',
        ...createSortingHeader('Type'),
        cell: renderTypeCell,
    },
    {
        accessorKey: 'category',
        ...createSortingHeader('Category'),
        cell: renderCategoryCell,
    },
    createCurrencyColumn<Product>({
        accessorKey: 'cost',
        label: 'Cost',
        currency: 'IDR',
        locale: 'id-ID',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }),
    createCurrencyColumn<Product>({
        accessorKey: 'selling_price',
        label: 'Price',
        currency: 'IDR',
        locale: 'id-ID',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }),
    {
        accessorKey: 'status',
        ...createSortingHeader('Status'),
        cell: renderStatusCell,
    },
    createActionsColumn<Product>(),
];
