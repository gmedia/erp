'use client';

import {
    createSelectColumn,
    createTextColumn,
    createDateColumn,
    createActionsColumn,
} from '@/utils/columns';
import { Position } from '@/types/position';
import { ColumnDef } from '@tanstack/react-table';

export const positionColumns: ColumnDef<Position>[] = [
    createSelectColumn<Position>(),
    createTextColumn<Position>({ accessorKey: 'name', label: 'Name' }),
    createDateColumn<Position>({ accessorKey: 'created_at', label: 'Created At' }),
    createDateColumn<Position>({ accessorKey: 'updated_at', label: 'Updated At' }),
    createActionsColumn<Position>({
        onEdit: () => {},
        onDelete: () => {},
    }),
];