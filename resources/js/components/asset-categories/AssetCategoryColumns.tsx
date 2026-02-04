'use client';

import { 
    createActionsColumn, 
    createDateColumn, 
    createSelectColumn, 
    createTextColumn,
    createNumberColumn
} from '@/utils/columns';
import { ColumnDef } from '@tanstack/react-table';
import { AssetCategory } from '@/types/asset-category';

export const assetCategoryColumns: ColumnDef<AssetCategory>[] = [
    createSelectColumn<AssetCategory>(),
    createTextColumn<AssetCategory>({ accessorKey: 'code', label: 'Code' }),
    createTextColumn<AssetCategory>({ accessorKey: 'name', label: 'Name' }),
    createNumberColumn<AssetCategory>({ 
        accessorKey: 'useful_life_months_default', 
        label: 'Default Useful Life (Months)',
        className: 'text-center'
    }),
    createDateColumn<AssetCategory>({ accessorKey: 'created_at', label: 'Created At' }),
    createDateColumn<AssetCategory>({ accessorKey: 'updated_at', label: 'Updated At' }),
    createActionsColumn<AssetCategory>(),
];
