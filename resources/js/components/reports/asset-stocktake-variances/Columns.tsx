import { createColumnHelper } from '@tanstack/react-table';
import { Badge } from '@/components/ui/badge';

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

const columnHelper = createColumnHelper<AssetStocktakeVarianceItem>();

export const varianceColumns = [
    columnHelper.accessor('stocktake_reference', {
        header: 'Stocktake Ref',
        cell: (info) => info.getValue() || '-',
    }),
    columnHelper.accessor('asset_code', {
        header: 'Asset Code',
        cell: (info) => info.getValue() || '-',
    }),
    columnHelper.accessor('asset_name', {
        header: 'Asset Name',
        cell: (info) => info.getValue() || '-',
    }),
    columnHelper.accessor('expected_branch_name', {
        id: 'expected_branch',
        header: 'Expected Branch',
        cell: (info) => info.getValue() || '-',
    }),
    columnHelper.accessor('expected_location_name', {
        id: 'expected_location',
        header: 'Expected Location',
        cell: (info) => info.getValue() || '-',
    }),
    columnHelper.accessor('found_branch_name', {
        id: 'found_branch',
        header: 'Found Branch',
        cell: (info) => info.getValue() || '-',
    }),
    columnHelper.accessor('found_location_name', {
        id: 'found_location',
        header: 'Found Location',
        cell: (info) => info.getValue() || '-',
    }),
    columnHelper.accessor('result', {
        header: 'Result',
        cell: (info) => {
            const val = info.getValue()?.toLowerCase();
            let variant: 'default' | 'secondary' | 'destructive' | 'outline' = 'default';
            if (val === 'damaged') variant = 'destructive';
            if (val === 'missing') variant = 'destructive';
            if (val === 'moved') variant = 'secondary';
            
            return <Badge variant={variant}>{val?.toUpperCase() || '-'}</Badge>;
        },
    }),
    columnHelper.accessor('notes', {
        header: 'Notes',
        cell: (info) => info.getValue() || '-',
        enableSorting: false,
    }),
    columnHelper.accessor('checked_at', {
        header: 'Checked At',
        cell: (info) => {
            const date = info.getValue();
            return date ? new Date(date).toLocaleString() : '-';
        },
    }),
    columnHelper.accessor('checked_by_name', {
        header: 'Checked By',
        cell: (info) => info.getValue() || '-',
        enableSorting: false,
    }),
];
