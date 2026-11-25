import { createReportTextColumn } from '@/components/common/ReportColumns';
import { Badge } from '@/components/ui/badge';
import {
    formatStatusLabel,
    stocktakeVarianceBadgeVariant,
} from '@/lib/status-badge';
import { createSortingHeader } from '@/utils/columns';
import { formatDateTimeByRegionalSettings } from '@/utils/date-format';
import { ColumnDef } from '@tanstack/react-table';

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
    createReportTextColumn<AssetStocktakeVarianceItem>({
        accessorKey: 'stocktake_reference',
        header: { key: 'column.stocktake_ref', fallback: 'Stocktake Ref' },
        getValue: (item) => item.stocktake_reference,
        className: 'font-medium',
        sortable: true,
    }),
    createReportTextColumn<AssetStocktakeVarianceItem>({
        accessorKey: 'asset_code',
        header: { key: 'column.asset_code', fallback: 'Asset Code' },
        getValue: (item) => item.asset_code,
        sortable: true,
    }),
    createReportTextColumn<AssetStocktakeVarianceItem>({
        accessorKey: 'asset_name',
        header: { key: 'column.asset_name', fallback: 'Asset Name' },
        getValue: (item) => item.asset_name,
        sortable: true,
    }),
    createReportTextColumn<AssetStocktakeVarianceItem>({
        id: 'expected_branch',
        accessorKey: 'expected_branch_name',
        header: {
            key: 'column.expected_branch',
            fallback: 'Expected Branch',
        },
        getValue: (item) => item.expected_branch_name,
        sortable: true,
    }),
    createReportTextColumn<AssetStocktakeVarianceItem>({
        id: 'expected_location',
        accessorKey: 'expected_location_name',
        header: {
            key: 'column.expected_location',
            fallback: 'Expected Location',
        },
        getValue: (item) => item.expected_location_name,
        sortable: true,
    }),
    createReportTextColumn<AssetStocktakeVarianceItem>({
        id: 'found_branch',
        accessorKey: 'found_branch_name',
        header: { key: 'column.found_branch', fallback: 'Found Branch' },
        getValue: (item) => item.found_branch_name,
        sortable: true,
    }),
    createReportTextColumn<AssetStocktakeVarianceItem>({
        id: 'found_location',
        accessorKey: 'found_location_name',
        header: { key: 'column.found_location', fallback: 'Found Location' },
        getValue: (item) => item.found_location_name,
        sortable: true,
    }),
    {
        accessorKey: 'result',
        ...createSortingHeader({ key: 'column.result', fallback: 'Result' }),
        cell: ({ row }) => {
            const val = row.getValue('result') as string;
            if (!val) return '-';
            return (
                <Badge variant={stocktakeVarianceBadgeVariant(val)}>
                    {formatStatusLabel(val)}
                </Badge>
            );
        },
    },
    createReportTextColumn<AssetStocktakeVarianceItem>({
        accessorKey: 'notes',
        header: { key: 'column.notes', fallback: 'Notes' },
        getValue: (item) => item.notes,
    }),
    createReportTextColumn<AssetStocktakeVarianceItem>({
        accessorKey: 'checked_at',
        header: { key: 'column.checked_at', fallback: 'Checked At' },
        getValue: (item) => formatDateTimeByRegionalSettings(item.checked_at),
        sortable: true,
    }),
    createReportTextColumn<AssetStocktakeVarianceItem>({
        accessorKey: 'checked_by_name',
        header: { key: 'column.checked_by', fallback: 'Checked By' },
        getValue: (item) => item.checked_by_name,
    }),
];
