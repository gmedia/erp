'use client';

import {
    createSelectColumn,
    createTextColumn,
    createDateColumn,
    createActionsColumn,
} from '@/utils/columns';
import { Department } from '@/types/department';
import { ColumnDef } from '@tanstack/react-table';

export const departmentColumns: ColumnDef<Department>[] = [
    createSelectColumn<Department>(),
    createTextColumn<Department>({ accessorKey: 'name', label: 'Name' }),
    createDateColumn<Department>({ accessorKey: 'created_at', label: 'Created At' }),
    createDateColumn<Department>({ accessorKey: 'updated_at', label: 'Updated At' }),
    createActionsColumn<Department>({
        onEdit: () => {},
        onDelete: () => {},
    }),
];