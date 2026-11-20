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
    createTextColumn<FiscalYear>({
        accessorKey: 'name',
        label: 'Name',
        primary: true,
    }),
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
            open: 'bg-success-soft text-success',
            closed: 'bg-warning-soft text-warning',
            locked: 'bg-destructive-soft text-destructive-foreground',
        },
    }),
    createDateColumn<FiscalYear>({
        accessorKey: 'created_at',
        label: 'Created At',
    }),
    createActionsColumn<FiscalYear>(),
];
