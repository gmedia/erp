'use client';

import { createActionsColumn, createDateColumn, createTextColumn, createBadgeColumn, createSelectColumn, createSortingHeader } from '@/utils/columns';
import { ColumnDef } from '@tanstack/react-table';
import { type CoaVersion } from '@/types/coa-version';

export const coaVersionColumns: ColumnDef<CoaVersion>[] = [
    createSelectColumn<CoaVersion>(),
    createTextColumn<CoaVersion>({ accessorKey: 'name', label: 'Name' }),
    {
        id: 'fiscal_year.name',
        accessorKey: 'fiscal_year.name',
        ...createSortingHeader('Fiscal Year'),
        cell: (info) => info.row.original.fiscal_year?.name || '-',
    },
    createBadgeColumn<CoaVersion>({
        accessorKey: 'status',
        label: 'Status',
        colorMap: {
            draft: 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-100',
            active: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
            archived: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        },
    }),
    createDateColumn<CoaVersion>({ accessorKey: 'created_at', label: 'Created At' }),
    createActionsColumn<CoaVersion>(),
];
