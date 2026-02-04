'use client';

import { ColumnDef } from '@tanstack/react-table';
import { Badge } from '@/components/ui/badge';

import {
    createActionsColumn,
    createSelectColumn,
    createSortingHeader,
    createTextColumn,
} from '@/utils/columns';

import { AssetModel } from '@/types/entity';

/**
 * Cell renderer for category column
 */
const renderCategoryCell = ({ row }: { row: { original: AssetModel } }) => {
    const category = row.original.category;
    return <Badge variant="outline">{category?.name || '-'}</Badge>;
};

/**
 * Cell renderer for specs column
 */
const renderSpecsCell = ({ row }: { row: { original: AssetModel } }) => {
    const specs = row.original.specs;
    if (!specs) return <span className="text-muted-foreground">-</span>;
    return (
        <span className="text-xs text-muted-foreground">
            {JSON.stringify(specs).slice(0, 50)}...
        </span>
    );
};

export const assetModelColumns: ColumnDef<AssetModel>[] = [
    createSelectColumn<AssetModel>(),
    createTextColumn<AssetModel>({ accessorKey: 'model_name', label: 'Model Name' }),
    createTextColumn<AssetModel>({ accessorKey: 'manufacturer', label: 'Manufacturer' }),
    {
        accessorKey: 'category',
        ...createSortingHeader('Category'),
        cell: renderCategoryCell,
    },
    {
        accessorKey: 'specs',
        header: 'Specs',
        cell: renderSpecsCell,
    },
    createActionsColumn<AssetModel>(),
];
