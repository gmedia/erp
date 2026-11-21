'use client';

import { type CoaVersion } from '@/types/coa-version';
import {
    createActionsColumn,
    createBadgeColumn,
    createDateColumn,
    createSelectColumn,
    createSortingHeader,
    createTextColumn,
} from '@/utils/columns';
import { ColumnDef } from '@tanstack/react-table';

export const coaVersionColumns: ColumnDef<CoaVersion>[] = [
    createSelectColumn<CoaVersion>(),
    createTextColumn<CoaVersion>({
        accessorKey: 'name',
        label: 'Name',
        primary: true,
    }),
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
            draft: 'bg-muted text-foreground',
            active: 'bg-success-soft text-success',
            archived: 'bg-warning-soft text-warning',
        },
    }),
    createDateColumn<CoaVersion>({
        accessorKey: 'created_at',
        label: 'Created At',
    }),
    createActionsColumn<CoaVersion>(),
];
