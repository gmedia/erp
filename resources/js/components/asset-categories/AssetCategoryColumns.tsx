'use client';

import { AssetCategory } from '@/types/asset-category';
import {
    createActionsColumn,
    createDateColumn,
    createNumberColumn,
    createSelectColumn,
    createTextColumn,
} from '@/utils/columns';
import { ColumnDef } from '@tanstack/react-table';

export const assetCategoryColumns: ColumnDef<AssetCategory>[] = [
    createSelectColumn<AssetCategory>(),
    createTextColumn<AssetCategory>({
        accessorKey: 'code',
        label: { key: 'column.code', fallback: 'Code' },
    }),
    createTextColumn<AssetCategory>({
        accessorKey: 'name',
        label: { key: 'column.name', fallback: 'Name' },
        primary: true,
    }),
    createNumberColumn<AssetCategory>({
        accessorKey: 'useful_life_months_default',
        label: {
            key: 'column.default_useful_life_months',
            fallback: 'Default Useful Life (Months)',
        },
        className: 'text-right tabular-nums',
    }),
    createDateColumn<AssetCategory>({
        accessorKey: 'created_at',
        label: { key: 'column.created_at', fallback: 'Created At' },
    }),
    createDateColumn<AssetCategory>({
        accessorKey: 'updated_at',
        label: { key: 'column.updated_at', fallback: 'Updated At' },
    }),
    createActionsColumn<AssetCategory>(),
];
