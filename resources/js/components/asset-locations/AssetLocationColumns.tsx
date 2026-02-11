'use client';

import { ColumnDef } from '@tanstack/react-table';
import { Badge } from '@/components/ui/badge';

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
    return parent?.name ? <Badge variant="secondary">{parent.name}</Badge> : <span className="text-muted-foreground">-</span>;
};

export const assetLocationColumns: ColumnDef<AssetLocation>[] = [
    createSelectColumn<AssetLocation>(),
    createTextColumn<AssetLocation>({ accessorKey: 'code', label: 'Code' }),
    createTextColumn<AssetLocation>({ accessorKey: 'name', label: 'Name' }),
    {
        accessorKey: 'branch',
        ...createSortingHeader('Branch'),
        cell: renderBranchCell,
    },
    {
        accessorKey: 'parent_id',
        ...createSortingHeader('Parent Location'),
        cell: renderParentCell,
    },
    createActionsColumn<AssetLocation>(),
];
