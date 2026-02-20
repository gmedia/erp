'use client';

import { AssetStocktake } from '@/types/asset-stocktake';
import { ColumnDef } from '@tanstack/react-table';
import {
    createActionsColumn,
    createBadgeColumn,
    createDateColumn,
    createSelectColumn,
    createSortingHeader,
    createTextColumn,
} from '@/utils/columns';

export const assetStocktakeColumns: ColumnDef<AssetStocktake>[] = [
    createSelectColumn<AssetStocktake>(),
    createTextColumn<AssetStocktake>({ accessorKey: 'reference', label: 'Reference' }),
    {
        id: 'branch',
        accessorFn: (row) => row.branch?.name ?? '',
        ...createSortingHeader('Branch'),
        cell: ({ row }) => row.original.branch?.name || '-',
    },
    createDateColumn<AssetStocktake>({ accessorKey: 'planned_at', label: 'Planned Date' }),
    createDateColumn<AssetStocktake>({ accessorKey: 'performed_at', label: 'Performed Date' }),
    createBadgeColumn<AssetStocktake>({
        accessorKey: 'status',
        label: 'Status',
        colorMap: {
            draft: 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-100',
            in_progress: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
            completed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
            cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        },
    }),
    {
        id: 'created_by',
        accessorFn: (row) => row.created_by?.name ?? '',
        ...createSortingHeader('Created By'),
        cell: ({ row }) => row.original.created_by?.name || '-',
    },
    createActionsColumn<AssetStocktake>(),
];
