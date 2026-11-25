'use client';

import {
    createActionsColumn,
    createDateColumn,
    createSelectColumn,
    createTextColumn,
} from '@/utils/columns';
import { ColumnDef } from '@tanstack/react-table';

export interface ProductCategory {
    id: number;
    name: string;
    description: string | null;
    created_at: string;
    updated_at: string;
}

export const productCategoryColumns: ColumnDef<ProductCategory>[] = [
    createSelectColumn<ProductCategory>(),
    createTextColumn<ProductCategory>({
        accessorKey: 'name',
        label: { key: 'column.name', fallback: 'Name' },
        primary: true,
    }),
    createTextColumn<ProductCategory>({
        accessorKey: 'description',
        label: { key: 'column.description', fallback: 'Description' },
        enableSorting: false,
    }),
    createDateColumn<ProductCategory>({
        accessorKey: 'created_at',
        label: { key: 'column.created_at', fallback: 'Created At' },
    }),
    createDateColumn<ProductCategory>({
        accessorKey: 'updated_at',
        label: { key: 'column.updated_at', fallback: 'Updated At' },
    }),
    createActionsColumn<ProductCategory>(),
];
