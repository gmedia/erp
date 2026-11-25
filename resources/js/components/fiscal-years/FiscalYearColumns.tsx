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
        label: { key: 'column.name', fallback: 'Name' },
        primary: true,
    }),
    createDateColumn<FiscalYear>({
        accessorKey: 'start_date',
        label: { key: 'column.start_date', fallback: 'Start Date' },
    }),
    createDateColumn<FiscalYear>({
        accessorKey: 'end_date',
        label: { key: 'column.end_date', fallback: 'End Date' },
    }),
    // Konteks tahun fiskal: open = berjalan (sukses), closed = finalisasi
    // (netral), locked = terkunci (danger, dari peta umum). 'open'/'closed'
    // di sini beda makna dari status dokumen — jadi override via variantMap.
    createStatusBadgeColumn<FiscalYear>({
        accessorKey: 'status',
        label: { key: 'column.status', fallback: 'Status' },
        variantMap: {
            open: 'info',
            closed: 'secondary',
            locked: 'destructive-soft',
        },
    }),
    createDateColumn<FiscalYear>({
        accessorKey: 'created_at',
        label: { key: 'column.created_at', fallback: 'Created At' },
    }),
    createActionsColumn<FiscalYear>(),
];
