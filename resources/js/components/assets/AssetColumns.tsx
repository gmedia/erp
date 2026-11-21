'use client';

import { Badge } from '@/components/ui/badge';
import { formatStatusLabel, statusBadgeVariant } from '@/lib/status-badge';
import { Asset } from '@/types/asset';
import {
    createActionsColumn,
    createCurrencyColumn,
    createDateColumn,
    createSelectColumn,
    createSortingHeader,
    createTextColumn,
} from '@/utils/columns';
import { ColumnDef } from '@tanstack/react-table';
import { Link } from 'react-router-dom';

const renderRelationCell =
    (key: keyof Asset) =>
    ({ row }: { row: { original: Asset } }) => {
        const val = row.original[key];
        return (
            <div>
                {typeof val === 'object' && val !== null
                    ? (val as { name: string }).name
                    : (val as string | number | null)}
            </div>
        );
    };

const createAssetRelationColumn = (
    accessorKey:
        | 'category'
        | 'branch'
        | 'location'
        | 'department'
        | 'employee'
        | 'supplier',
    label: string,
): ColumnDef<Asset> => ({
    accessorKey,
    ...createSortingHeader(label),
    cell: renderRelationCell(accessorKey),
});

const renderStatusCell = ({ row }: { row: { original: Asset } }) => {
    const status = row.original.status;
    return (
        <Badge variant={statusBadgeVariant(status)}>
            {formatStatusLabel(status)}
        </Badge>
    );
};

export const assetColumns: ColumnDef<Asset>[] = [
    createSelectColumn<Asset>(),
    createTextColumn<Asset>({ accessorKey: 'asset_code', label: 'Code' }),
    {
        accessorKey: 'name',
        ...createSortingHeader('Name'),
        cell: ({ row }) => (
            <Link
                to={`/assets/${row.original.ulid}`}
                className="font-medium text-primary hover:underline"
            >
                {row.original.name}
            </Link>
        ),
    },
    createAssetRelationColumn('category', 'Category'),
    createAssetRelationColumn('branch', 'Branch'),
    createAssetRelationColumn('location', 'Location'),
    createAssetRelationColumn('department', 'Department'),
    createAssetRelationColumn('employee', 'Employee'),
    createAssetRelationColumn('supplier', 'Supplier'),
    {
        accessorKey: 'status',
        ...createSortingHeader('Status'),
        cell: renderStatusCell,
    },
    createCurrencyColumn<Asset>({
        accessorKey: 'purchase_cost',
        label: 'Cost',
        currency: 'IDR',
        locale: 'id-ID',
        minimumFractionDigits: 0,
    }),
    createDateColumn<Asset>({
        accessorKey: 'purchase_date',
        label: 'Purchase Date',
    }),
    createActionsColumn<Asset>({
        viewPath: (asset) => `/assets/${asset.ulid}`,
    }),
];
