import { ColumnDef } from '@tanstack/react-table';
import { Badge } from '@/components/ui/badge';
import { createSortingHeader } from '@/utils/columns';

export interface AssetStocktakeVarianceItem {
    id: number;
    asset_stocktake_id: number;
    stocktake_reference: string;
    asset_id: number;
    asset_code: string;
    asset_name: string;
    expected_branch_id: number;
    expected_branch_name: string;
    expected_location_id: number;
    expected_location_name: string;
    found_branch_id: number;
    found_branch_name: string;
    found_location_id: number;
    found_location_name: string;
    result: string;
    notes: string;
    checked_at: string;
    checked_by: string;
    checked_by_name: string;
}

export const varianceColumns: ColumnDef<AssetStocktakeVarianceItem>[] = [
    {
        accessorKey: 'stocktake_reference',
        ...createSortingHeader('Stocktake Ref'),
        cell: ({ row }) => <div className="font-medium">{row.getValue('stocktake_reference') || '-'}</div>,
    },
    {
        accessorKey: 'asset_code',
        ...createSortingHeader('Asset Code'),
        cell: ({ row }) => <div>{row.getValue('asset_code') || '-'}</div>,
    },
    {
        accessorKey: 'asset_name',
        ...createSortingHeader('Asset Name'),
        cell: ({ row }) => <div>{row.getValue('asset_name') || '-'}</div>,
    },
    {
        accessorKey: 'expected_branch_name',
        id: 'expected_branch',
        ...createSortingHeader('Expected Branch'),
        cell: ({ row }) => <div>{row.getValue('expected_branch') || '-'}</div>,
    },
    {
        accessorKey: 'expected_location_name',
        id: 'expected_location',
        ...createSortingHeader('Expected Location'),
        cell: ({ row }) => <div>{row.getValue('expected_location') || '-'}</div>,
    },
    {
        accessorKey: 'found_branch_name',
        id: 'found_branch',
        ...createSortingHeader('Found Branch'),
        cell: ({ row }) => <div>{row.getValue('found_branch') || '-'}</div>,
    },
    {
        accessorKey: 'found_location_name',
        id: 'found_location',
        ...createSortingHeader('Found Location'),
        cell: ({ row }) => <div>{row.getValue('found_location') || '-'}</div>,
    },
    {
        accessorKey: 'result',
        ...createSortingHeader('Result'),
        cell: ({ row }) => {
            const val = (row.getValue('result') as string)?.toLowerCase();
            let variant: 'default' | 'secondary' | 'destructive' | 'outline' = 'default';
            if (val === 'damaged') variant = 'destructive';
            if (val === 'missing') variant = 'destructive';
            if (val === 'moved') variant = 'secondary';
            
            return <Badge variant={variant}>{val?.toUpperCase() || '-'}</Badge>;
        },
    },
    {
        accessorKey: 'notes',
        header: 'Notes',
        cell: ({ row }) => <div>{row.getValue('notes') || '-'}</div>,
        enableSorting: false,
    },
    {
        accessorKey: 'checked_at',
        ...createSortingHeader('Checked At'),
        cell: ({ row }) => {
            const date = row.getValue('checked_at') as string;
            return <div>{date ? new Date(date).toLocaleString() : '-'}</div>;
        },
    },
    {
        accessorKey: 'checked_by_name',
        header: 'Checked By',
        cell: ({ row }) => <div>{row.getValue('checked_by_name') || '-'}</div>,
        enableSorting: false,
    },
];
