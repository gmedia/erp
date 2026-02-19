'use client';

import { Badge } from '@/components/ui/badge';
import { type AssetMaintenance } from '@/types/asset-maintenance';
import { createActionsColumn, createSelectColumn, createSortingHeader } from '@/utils/columns';
import { type ColumnDef } from '@tanstack/react-table';
import { format } from 'date-fns';

export const assetMaintenanceColumns: ColumnDef<AssetMaintenance>[] = [
    createSelectColumn<AssetMaintenance>(),
    {
        accessorKey: 'asset',
        ...createSortingHeader('Asset'),
        cell: ({ row }) => {
            const asset = row.original.asset;
            if (!asset) return '-';
            return (
                <div className="flex flex-col">
                    <span className="font-medium">{asset.name || '-'}</span>
                    <span className="text-xs text-muted-foreground font-mono">
                        {asset.asset_code || '-'}
                    </span>
                </div>
            );
        },
    },
    {
        accessorKey: 'maintenance_type',
        ...createSortingHeader('Type'),
        cell: ({ row }) => (
            <Badge variant="outline" className="capitalize">
                {row.getValue('maintenance_type') as string}
            </Badge>
        ),
    },
    {
        accessorKey: 'status',
        ...createSortingHeader('Status'),
        cell: ({ row }) => {
            const status = row.getValue('status') as string;
            const variant = status === 'completed'
                ? 'default'
                : status === 'cancelled'
                    ? 'destructive'
                    : 'secondary';

            return (
                <Badge variant={variant} className="capitalize">
                    {status}
                </Badge>
            );
        },
    },
    {
        accessorKey: 'scheduled_at',
        ...createSortingHeader('Scheduled'),
        cell: ({ row }) => {
            const date = row.getValue('scheduled_at') as string | null;
            return date ? format(new Date(date), 'PPP') : '-';
        },
    },
    {
        accessorKey: 'performed_at',
        ...createSortingHeader('Performed'),
        cell: ({ row }) => {
            const date = row.getValue('performed_at') as string | null;
            return date ? format(new Date(date), 'PPP') : '-';
        },
    },
    {
        accessorKey: 'supplier',
        ...createSortingHeader('Supplier'),
        cell: ({ row }) => row.original.supplier || '-',
    },
    {
        accessorKey: 'notes',
        ...createSortingHeader('Notes'),
        cell: ({ row }) => (
            <div className="max-w-[220px] truncate text-muted-foreground">
                {row.original.notes || '-'}
            </div>
        ),
    },
    {
        accessorKey: 'cost',
        ...createSortingHeader('Cost'),
        cell: ({ row }) => {
            const cost = Number(row.original.cost);
            if (Number.isNaN(cost)) return '-';
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0,
            }).format(cost);
        },
    },
    createActionsColumn<AssetMaintenance>(),
];
