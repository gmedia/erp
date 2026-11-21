'use client';

import { Badge } from '@/components/ui/badge';
import { formatStatusLabel } from '@/lib/status-badge';
import { type AssetMaintenance } from '@/types/asset-maintenance';
import {
    createActionsColumn,
    createSelectColumn,
    createSortingHeader,
    createStatusBadgeColumn,
} from '@/utils/columns';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import { formatCurrencyByRegionalSettings } from '@/utils/number-format';
import { type ColumnDef } from '@tanstack/react-table';

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
                    <span className="font-mono text-xs text-muted-foreground">
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
            <Badge variant="outline">
                {formatStatusLabel(row.getValue('maintenance_type') as string)}
            </Badge>
        ),
    },
    createStatusBadgeColumn<AssetMaintenance>({
        accessorKey: 'status',
        label: 'Status',
    }),
    {
        accessorKey: 'scheduled_at',
        ...createSortingHeader('Scheduled'),
        cell: ({ row }) => {
            const date = row.getValue('scheduled_at') as string | null;
            return formatDateByRegionalSettings(date);
        },
    },
    {
        accessorKey: 'performed_at',
        ...createSortingHeader('Performed'),
        cell: ({ row }) => {
            const date = row.getValue('performed_at') as string | null;
            return formatDateByRegionalSettings(date);
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
            return formatCurrencyByRegionalSettings(cost, {
                locale: 'id-ID',
                currency: 'IDR',
                minimumFractionDigits: 0,
            });
        },
    },
    createActionsColumn<AssetMaintenance>(),
];
