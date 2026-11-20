'use client';

import { GenericActions } from '@/components/common/ActionsDropdown';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { AssetStocktake } from '@/types/asset-stocktake';
import {
    createBadgeColumn,
    createDateColumn,
    createSelectColumn,
    createSortingHeader,
    createTextColumn,
    type CustomTableMeta,
} from '@/utils/columns';
import { ColumnDef } from '@tanstack/react-table';
import { Link } from 'react-router-dom';

export const assetStocktakeColumns: ColumnDef<AssetStocktake>[] = [
    createSelectColumn<AssetStocktake>(),
    createTextColumn<AssetStocktake>({
        accessorKey: 'reference',
        label: 'Reference',
    }),
    {
        id: 'branch',
        accessorFn: (row) => row.branch?.name ?? '',
        ...createSortingHeader('Branch'),
        cell: ({ row }) => row.original.branch?.name || '-',
    },
    createDateColumn<AssetStocktake>({
        accessorKey: 'planned_at',
        label: 'Planned Date',
    }),
    createDateColumn<AssetStocktake>({
        accessorKey: 'performed_at',
        label: 'Performed Date',
    }),
    createBadgeColumn<AssetStocktake>({
        accessorKey: 'status',
        label: 'Status',
        colorMap: {
            draft: 'bg-muted text-foreground',
            in_progress: 'bg-info/10 text-info',
            completed: 'bg-success-soft text-success',
            cancelled: 'bg-destructive-soft text-destructive-foreground',
        },
    }),
    {
        id: 'created_by',
        accessorFn: (row) => row.created_by?.name ?? '',
        ...createSortingHeader('Created By'),
        cell: ({ row }) => row.original.created_by?.name || '-',
    },
    {
        id: 'actions_custom',
        enableHiding: false,
        cell: ({ row, table }) => {
            const item = row.original;
            const meta = table.options.meta as
                | CustomTableMeta<AssetStocktake>
                | undefined;
            return (
                <GenericActions
                    item={item}
                    onView={meta?.onView}
                    onEdit={meta?.onEdit}
                    onDelete={meta?.onDelete}
                    extraItems={[
                        <DropdownMenuItem key="perform" asChild>
                            <Link
                                to={`/asset-stocktakes/${item.ulid}/perform`}
                                className="w-full"
                            >
                                Perform
                            </Link>
                        </DropdownMenuItem>,
                    ]}
                />
            );
        },
    },
];
