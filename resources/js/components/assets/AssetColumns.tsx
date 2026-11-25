'use client';

import { StatusBadgeLabel } from '@/components/common/StatusBadgeLabel';
import type { TranslatableLabel } from '@/components/common/TranslatedText';
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
        const label =
            typeof val === 'object' && val !== null
                ? (val as { name: string }).name
                : (val as string | number | null);
        return <div>{label ?? '-'}</div>;
    };

const createAssetRelationColumn = (
    accessorKey:
        | 'category'
        | 'branch'
        | 'location'
        | 'department'
        | 'employee'
        | 'supplier',
    label: TranslatableLabel,
): ColumnDef<Asset> => ({
    accessorKey,
    ...createSortingHeader(label),
    cell: renderRelationCell(accessorKey),
});

const renderStatusCell = ({ row }: { row: { original: Asset } }) => (
    <StatusBadgeLabel status={row.original.status} />
);

export const assetColumns: ColumnDef<Asset>[] = [
    createSelectColumn<Asset>(),
    createTextColumn<Asset>({
        accessorKey: 'asset_code',
        label: { key: 'column.code', fallback: 'Code' },
    }),
    {
        accessorKey: 'name',
        ...createSortingHeader({ key: 'column.name', fallback: 'Name' }),
        cell: ({ row }) => (
            <Link
                to={`/assets/${row.original.ulid}`}
                className="font-medium text-primary hover:underline"
            >
                {row.original.name}
            </Link>
        ),
    },
    createAssetRelationColumn('category', {
        key: 'column.category',
        fallback: 'Category',
    }),
    createAssetRelationColumn('branch', {
        key: 'column.branch',
        fallback: 'Branch',
    }),
    createAssetRelationColumn('location', {
        key: 'column.location',
        fallback: 'Location',
    }),
    createAssetRelationColumn('department', {
        key: 'column.department',
        fallback: 'Department',
    }),
    createAssetRelationColumn('employee', {
        key: 'column.employee',
        fallback: 'Employee',
    }),
    createAssetRelationColumn('supplier', {
        key: 'column.supplier',
        fallback: 'Supplier',
    }),
    {
        accessorKey: 'status',
        ...createSortingHeader({ key: 'column.status', fallback: 'Status' }),
        cell: renderStatusCell,
    },
    createCurrencyColumn<Asset>({
        accessorKey: 'purchase_cost',
        label: { key: 'column.purchase_cost', fallback: 'Cost' },
        currency: 'IDR',
        locale: 'id-ID',
        minimumFractionDigits: 0,
    }),
    createDateColumn<Asset>({
        accessorKey: 'purchase_date',
        label: { key: 'column.purchase_date', fallback: 'Purchase Date' },
    }),
    createActionsColumn<Asset>({
        viewPath: (asset) => `/assets/${asset.ulid}`,
    }),
];
