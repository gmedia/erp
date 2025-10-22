'use client';

import {
    createActionsColumn,
    createSelectColumn,
    createSortingHeader,
} from '@/components/common/BaseColumns';
import { formatDate } from '@/lib/utils';
import { Position } from '@/types/position';
import { ColumnDef } from '@tanstack/react-table';

export function getPositionColumns(options: {
    onEdit?: (position: Position) => void;
    onDelete?: (position: Position) => void;
    onView?: (position: Position) => void;
}) {
    const { onEdit, onDelete, onView } = options;
    const columns: ColumnDef<Position>[] = [
        createSelectColumn<Position>(),
        {
            accessorKey: 'name',
            ...createSortingHeader<Position>('Name'),
        },
        {
            accessorKey: 'created_at',
            ...createSortingHeader<Position>('Created At'),
            cell: ({ row }) => {
                return <div>{formatDate(row.getValue('created_at'))}</div>;
            },
        },
        {
            accessorKey: 'updated_at',
            ...createSortingHeader<Position>('Updated At'),
            cell: ({ row }) => {
                return <div>{formatDate(row.getValue('updated_at'))}</div>;
            },
        },
        {
            ...createActionsColumn<Position>({
                onView,
                onEdit: onEdit ? (item) => onEdit(item) : () => {},
                onDelete: onDelete ? (item) => onDelete(item) : () => {},
            }),
        },
    ];
    return columns;
}
export const positionColumns: ColumnDef<Position>[] = getPositionColumns({});
