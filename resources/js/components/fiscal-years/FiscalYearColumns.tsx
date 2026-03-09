'use client';

import { type FiscalYear } from '@/types/entity';
import {
    createActionsColumn,
    createBadgeColumn,
    createDateColumn,
    createSelectColumn,
    createTextColumn,
} from '@/utils/columns';
import { ColumnDef } from '@tanstack/react-table';

export const fiscalYearColumns: ColumnDef<FiscalYear>[] = [
    createSelectColumn<FiscalYear>(),
    createTextColumn<FiscalYear>({ accessorKey: 'name', label: 'Name' }),
    createDateColumn<FiscalYear>({
        accessorKey: 'start_date',
        label: 'Start Date',
    }),
    createDateColumn<FiscalYear>({
        accessorKey: 'end_date',
        label: 'End Date',
    }),
    createBadgeColumn<FiscalYear>({
        accessorKey: 'status',
        label: 'Status',
        colorMap: {
            open: 'bg-green-100 text-green-800',
            closed: 'bg-yellow-100 text-yellow-800',
            locked: 'bg-red-100 text-red-800',
        },
    }),
    createDateColumn<FiscalYear>({
        accessorKey: 'created_at',
        label: 'Created At',
    }),
    createActionsColumn<FiscalYear>(),
];
