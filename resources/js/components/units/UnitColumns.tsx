'use client';

import { createActionsColumn, createDateColumn, createSelectColumn, createTextColumn } from '@/utils/columns';
import { ColumnDef } from '@tanstack/react-table';

export interface Unit {
    id: number;
    name: string;
    symbol: string | null;
    created_at: string;
    updated_at: string;
}

export const unitColumns: ColumnDef<Unit>[] = [
    createSelectColumn<Unit>(),
    createTextColumn<Unit>({ accessorKey: 'name', label: 'Name' }),
    createTextColumn<Unit>({
        accessorKey: 'symbol',
        label: 'Symbol',
        enableSorting: false,
    }),
    createDateColumn<Unit>({ accessorKey: 'created_at', label: 'Created At' }),
    createDateColumn<Unit>({ accessorKey: 'updated_at', label: 'Updated At' }),
    createActionsColumn<Unit>(),
];
