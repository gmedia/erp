'use client';

import {
    createActionsColumn,
    createDateColumn,
    createSelectColumn,
    createTextColumn,
} from '@/utils/columns';
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
    createTextColumn<Unit>({
        accessorKey: 'name',
        label: { key: 'column.name', fallback: 'Name' },
        primary: true,
    }),
    createTextColumn<Unit>({
        accessorKey: 'symbol',
        label: { key: 'column.symbol', fallback: 'Symbol' },
        enableSorting: false,
    }),
    createDateColumn<Unit>({
        accessorKey: 'created_at',
        label: { key: 'column.created_at', fallback: 'Created At' },
    }),
    createDateColumn<Unit>({
        accessorKey: 'updated_at',
        label: { key: 'column.updated_at', fallback: 'Updated At' },
    }),
    createActionsColumn<Unit>(),
];
