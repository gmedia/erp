'use client';

import { GenericActions } from '@/components/common/ActionsDropdown';
import { LabelText } from '@/components/common/TranslatedText';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { AssetStocktake } from '@/types/asset-stocktake';
import {
    createDateColumn,
    createSelectColumn,
    createSortingHeader,
    createStatusBadgeColumn,
    createTextColumn,
    type CustomTableMeta,
} from '@/utils/columns';
import { ColumnDef } from '@tanstack/react-table';
import { Link } from 'react-router-dom';

export const assetStocktakeColumns: ColumnDef<AssetStocktake>[] = [
    createSelectColumn<AssetStocktake>(),
    createTextColumn<AssetStocktake>({
        accessorKey: 'reference',
        label: { key: 'column.reference', fallback: 'Reference' },
    }),
    {
        id: 'branch',
        accessorFn: (row) => row.branch?.name ?? '',
        ...createSortingHeader({ key: 'column.branch', fallback: 'Branch' }),
        cell: ({ row }) => row.original.branch?.name || '-',
    },
    createDateColumn<AssetStocktake>({
        accessorKey: 'planned_at',
        label: { key: 'column.planned_date', fallback: 'Planned Date' },
    }),
    createDateColumn<AssetStocktake>({
        accessorKey: 'performed_at',
        label: { key: 'column.performed_date', fallback: 'Performed Date' },
    }),
    createStatusBadgeColumn<AssetStocktake>({
        accessorKey: 'status',
        label: { key: 'column.status', fallback: 'Status' },
    }),
    {
        id: 'created_by',
        accessorFn: (row) => row.created_by?.name ?? '',
        ...createSortingHeader({
            key: 'column.created_by',
            fallback: 'Created By',
        }),
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
                                <LabelText
                                    label={{
                                        key: 'column.perform',
                                        fallback: 'Perform',
                                    }}
                                />
                            </Link>
                        </DropdownMenuItem>,
                    ]}
                />
            );
        },
    },
];
