'use client';

import {
    createSelectColumn,
    createTextColumn,
    createDateColumn,
    createActionsColumn,
} from '@/utils/columns';
import { ColumnDef } from '@tanstack/react-table';

interface Position {
    id: number;
    name: string;
    created_at: string;
    updated_at: string;
}

export const positionColumns: ColumnDef<Position>[] = [
    createSelectColumn<Position>(),
    createTextColumn<Position>({ accessorKey: 'name', label: 'Name' }),
    createDateColumn<Position>({ accessorKey: 'created_at', label: 'Created At' }),
    createDateColumn<Position>({ accessorKey: 'updated_at', label: 'Updated At' }),
    createActionsColumn<Position>(),
];
