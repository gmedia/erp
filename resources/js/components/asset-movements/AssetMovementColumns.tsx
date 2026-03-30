'use client';

import { Badge } from '@/components/ui/badge';
import {
    createActionsColumn,
    createSelectColumn,
    createSortingHeader,
} from '@/utils/columns';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import { ColumnDef } from '@tanstack/react-table';

export interface AssetMovement {
    id: number;
    movement_type: string;
    moved_at: string;
    asset: {
        asset_code: string;
        name: string;
    };
    from_branch?: string;
    to_branch?: string;
    from_location?: string;
    to_location?: string;
    from_department?: string;
    to_department?: string;
    from_employee?: string;
    to_employee?: string;
    reference?: string;
    notes?: string;
    created_by: string;
}

export const assetMovementColumns: ColumnDef<AssetMovement>[] = [
    createSelectColumn<AssetMovement>(),
    {
        accessorKey: 'asset',
        ...createSortingHeader('Asset'),
        cell: ({ row }) => {
            const asset = row.original.asset;
            if (!asset) return '-';
            return (
                <div className="flex flex-col">
                    <span className="font-medium">{asset.name}</span>
                    <span className="font-mono text-xs text-muted-foreground">
                        {asset.asset_code}
                    </span>
                </div>
            );
        },
    },
    {
        accessorKey: 'movement_type',
        ...createSortingHeader('Type'),
        cell: ({ row }) => (
            <Badge variant="outline" className="capitalize">
                {row.getValue('movement_type')}
            </Badge>
        ),
    },
    {
        accessorKey: 'moved_at',
        ...createSortingHeader('Date'),
        cell: ({ row }) => {
            const date = row.getValue('moved_at') as string;
            return formatDateByRegionalSettings(date);
        },
    },
    {
        id: 'origin',
        header: 'Origin',
        cell: ({ row }) => {
            const m = row.original;
            return (
                <div className="text-xs">
                    {m.from_branch && (
                        <div className="font-medium">{m.from_branch}</div>
                    )}
                    {m.from_location && (
                        <div className="text-muted-foreground">
                            {m.from_location}
                        </div>
                    )}
                    {m.from_employee && (
                        <div className="text-primary">{m.from_employee}</div>
                    )}
                </div>
            );
        },
    },
    {
        id: 'destination',
        header: 'Destination',
        cell: ({ row }) => {
            const m = row.original;
            return (
                <div className="text-xs">
                    {m.to_branch && (
                        <div className="font-medium">{m.to_branch}</div>
                    )}
                    {m.to_location && (
                        <div className="text-muted-foreground">
                            {m.to_location}
                        </div>
                    )}
                    {m.to_employee && (
                        <div className="text-primary">{m.to_employee}</div>
                    )}
                </div>
            );
        },
    },
    {
        accessorKey: 'reference',
        ...createSortingHeader('Ref/Notes'),
        cell: ({ row }) => (
            <div className="max-w-[200px]">
                <div className="text-xs font-semibold">
                    {row.original.reference || '-'}
                </div>
                <div className="truncate text-xs text-muted-foreground">
                    {row.original.notes}
                </div>
            </div>
        ),
    },
    {
        accessorKey: 'created_by',
        ...createSortingHeader('PIC'),
    },
    createActionsColumn<AssetMovement>(),
];
