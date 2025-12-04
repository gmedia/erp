'use client';

import {
    createActionsColumn,
    createSelectColumn,
} from '@/components/common/BaseColumns';
import { createDateColumn, createTextColumn } from '@/components/common/ColumnUtils';
import { Position } from '@/types/position';
import { ColumnDef } from '@tanstack/react-table';

export const positionColumns: ColumnDef<Position>[] = [
    createSelectColumn<Position>(),
    createTextColumn<Position>('name', 'Name'),
    createDateColumn<Position>('created_at', 'Created At'),
    createDateColumn<Position>('updated_at', 'Updated At'),
    createActionsColumn<Position>({
        onEdit: () => {},
        onDelete: () => {},
    }),
];