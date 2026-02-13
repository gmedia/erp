'use client';

import { createActionsColumn, createDateColumn, createSelectColumn, createTextColumn } from '@/utils/columns';
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
    createTextColumn<ProductCategory>({ accessorKey: 'name', label: 'Name' }),
    createTextColumn<ProductCategory>({
        accessorKey: 'description',
        label: 'Description',
        enableSorting: false,
    }),
    createDateColumn<ProductCategory>({ accessorKey: 'created_at', label: 'Created At' }),
    createDateColumn<ProductCategory>({ accessorKey: 'updated_at', label: 'Updated At' }),
    createActionsColumn<ProductCategory>(),
];
