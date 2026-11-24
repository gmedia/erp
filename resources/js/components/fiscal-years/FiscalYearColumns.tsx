'use client';

import { type FiscalYear } from '@/types/entity';
import {
    createActionsColumn,
    createDateColumn,
    createSelectColumn,
    createStatusBadgeColumn,
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
    // Konteks tahun fiskal: open = berjalan (sukses), closed = finalisasi
    // (netral), locked = terkunci (danger, dari peta umum). 'open'/'closed'
    // di sini beda makna dari status dokumen — jadi override via variantMap.
    createStatusBadgeColumn<FiscalYear>({
        accessorKey: 'status',
        label: 'Status',
        variantMap: {
            open: 'info',
            closed: 'secondary',
            locked: 'destructive-soft',
        },
    }),
    createDateColumn<FiscalYear>({
        accessorKey: 'created_at',
        label: 'Created At',
    }),
    createActionsColumn<FiscalYear>(),
];
