'use client';

import { EntityColumnDef } from '@/components/common/EntityDataTable';
import { AssetStocktake } from '@/types/asset-stocktake';
import { Badge } from '@/components/ui/badge';

export const assetStocktakeColumns: EntityColumnDef<AssetStocktake>[] = [
    {
        accessorKey: 'reference',
        header: 'Reference',
    },
    {
        accessorKey: 'branch.name',
        header: 'Branch',
    },
    {
        accessorKey: 'planned_at',
        header: 'Planned Date',
        cell: ({ row }) => new Date(row.original.planned_at).toLocaleDateString(),
    },
    {
        accessorKey: 'performed_at',
        header: 'Performed Date',
        cell: ({ row }) => row.original.performed_at ? new Date(row.original.performed_at).toLocaleDateString() : '-',
    },
    {
        accessorKey: 'status',
        header: 'Status',
        cell: ({ row }) => {
            const status = row.original.status;
            let variant: 'default' | 'secondary' | 'destructive' | 'outline' = 'default';
            
            switch (status) {
                case 'draft': variant = 'secondary'; break;
                case 'in_progress': variant = 'default'; break;
                case 'completed': variant = 'outline'; break;
                case 'cancelled': variant = 'destructive'; break;
            }

            return <Badge variant={variant}>{status}</Badge>;
        },
    },
    {
        accessorKey: 'created_by.name',
        header: 'Created By',
    },
];
