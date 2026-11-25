'use client';

import { Badge } from '@/components/ui/badge';
import { ColumnDef } from '@tanstack/react-table';

import {
    createActionsColumn,
    createSelectColumn,
    createSortingHeader,
    createTextColumn,
} from '@/utils/columns';

import { AssetLocation } from '@/types/entity';

/**
 * Cell renderer for branch column
 */
const renderBranchCell = ({ row }: { row: { original: AssetLocation } }) => {
    const branch = row.original.branch;
    return <Badge variant="outline">{branch?.name || '-'}</Badge>;
};

/**
 * Cell renderer for parent location column
 */
const renderParentCell = ({ row }: { row: { original: AssetLocation } }) => {
    const parent = row.original.parent;
    return parent?.name ? (
        <Badge variant="secondary">{parent.name}</Badge>
    ) : (
        <span className="text-muted-foreground">-</span>
    );
};

export const assetLocationColumns: ColumnDef<AssetLocation>[] = [
    createSelectColumn<AssetLocation>(),
    createTextColumn<AssetLocation>({
        accessorKey: 'code',
        label: { key: 'column.code', fallback: 'Code' },
    }),
    createTextColumn<AssetLocation>({
        accessorKey: 'name',
        label: { key: 'column.name', fallback: 'Name' },
        primary: true,
    }),
    {
        accessorKey: 'branch',
        ...createSortingHeader({ key: 'column.branch', fallback: 'Branch' }),
        cell: renderBranchCell,
    },
    {
        accessorKey: 'parent',
        ...createSortingHeader({
            key: 'column.parent_location',
            fallback: 'Parent Location',
        }),
        cell: renderParentCell,
    },
    createActionsColumn<AssetLocation>(),
];
