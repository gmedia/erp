'use client';

import { ColumnDef } from '@tanstack/react-table';
import { Link, router } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import {
    createActionsColumn,
    createCurrencyColumn,
    createDateColumn,
    createSelectColumn,
    createSortingHeader,
    createTextColumn,
} from '@/utils/columns';
import { Asset } from '@/types/asset';

const renderRelationCell = (key: keyof Asset) => ({ row }: { row: { original: Asset } }) => {
    const val = row.original[key];
    return <div>{typeof val === 'object' && val !== null ? (val as any).name : val}</div>;
};

const renderStatusCell = ({ row }: { row: { original: Asset } }) => {
    const status = row.original.status;
    const variants: Record<string, "default" | "secondary" | "destructive" | "outline"> = {
        draft: 'outline',
        active: 'default',
        maintenance: 'secondary',
        disposed: 'destructive',
        lost: 'destructive',
    };
    return <Badge variant={variants[status] || 'default'}>{status}</Badge>;
};

export const assetColumns: ColumnDef<Asset>[] = [
    createSelectColumn<Asset>(),
    createTextColumn<Asset>({ accessorKey: 'asset_code', label: 'Code' }),
    {
        accessorKey: 'name',
        ...createSortingHeader('Name'),
        cell: ({ row }) => (
            <Link
                href={`/assets/${row.original.ulid}`}
                className="font-medium text-primary hover:underline"
            >
                {row.original.name}
            </Link>
        ),
    },
    {
        accessorKey: 'category',
        ...createSortingHeader('Category'),
        cell: renderRelationCell('category'),
    },
    {
        accessorKey: 'branch',
        ...createSortingHeader('Branch'),
        cell: renderRelationCell('branch'),
    },
    {
        accessorKey: 'location',
        ...createSortingHeader('Location'),
        cell: renderRelationCell('location'),
    },
    {
        accessorKey: 'department',
        ...createSortingHeader('Department'),
        cell: renderRelationCell('department'),
    },
    {
        accessorKey: 'employee',
        ...createSortingHeader('Employee'),
        cell: renderRelationCell('employee'),
    },
    {
        accessorKey: 'supplier',
        ...createSortingHeader('Supplier'),
        cell: renderRelationCell('supplier'),
    },
    {
        accessorKey: 'status',
        ...createSortingHeader('Status'),
        cell: renderStatusCell,
    },
    createCurrencyColumn<Asset>({
        accessorKey: 'purchase_cost',
        label: 'Cost',
        currency: 'IDR',
        locale: 'id-ID',
        minimumFractionDigits: 0,
    }),
    createDateColumn<Asset>({
        accessorKey: 'purchase_date',
        label: 'Purchase Date',
    }),
    createActionsColumn<Asset>({
        viewPath: (asset) => `/assets/${asset.ulid}`,
    }),
];
