'use client';

import {
    createActionsColumn,
    createSelectColumn,
} from '@/components/common/BaseColumns';
import { createDateColumn, createTextColumn } from '@/components/common/ColumnUtils';
import { Department } from '@/types/department';
import { ColumnDef } from '@tanstack/react-table';

export const departmentColumns: ColumnDef<Department>[] = [
    createSelectColumn<Department>(),
    createTextColumn<Department>('name', 'Name'),
    createDateColumn<Department>('created_at', 'Created At'),
    createDateColumn<Department>('updated_at', 'Updated At'),
    {
        ...createActionsColumn<Department>({
            onEdit: () => {},
            onDelete: () => {},
        }),
    },
];