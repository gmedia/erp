import { ColumnDef } from '@tanstack/react-table';
import { formatCurrency } from '@/lib/utils';
import { createSortingHeader } from '@/utils/columns';

export interface BookValueDepreciationReportItem {
    id: number;
    ulid: string;
    asset_code: string;
    name: string;
    category_name: string | null;
    branch_name: string | null;
    purchase_date: string | null;
    purchase_cost: number;
    salvage_value: number;
    useful_life_months: number;
    accumulated_depreciation: number;
    book_value: number;
}export const bookValueDepreciationColumns: ColumnDef<BookValueDepreciationReportItem>[] = [
    {
        accessorKey: 'asset_code',
        ...createSortingHeader('Asset Code'),
        cell: ({ row }) => <div className="font-medium">{row.getValue('asset_code')}</div>,
    },
    {
        accessorKey: 'name',
        ...createSortingHeader('Asset Name'),
        cell: ({ row }) => <div>{row.getValue('name')}</div>,
    },
    {
        accessorKey: 'category_name',
        header: 'Category',
        cell: ({ row }) => <div>{row.getValue('category_name') || '-'}</div>,
        enableSorting: false,
    },
    {
        accessorKey: 'branch_name',
        header: 'Branch',
        cell: ({ row }) => <div>{row.getValue('branch_name') || '-'}</div>,
        enableSorting: false,
    },
    {
        accessorKey: 'purchase_date',
        ...createSortingHeader('Purchase Date'),
        cell: ({ row }) => <div>{row.getValue('purchase_date') || '-'}</div>,
    },
    {
        accessorKey: 'purchase_cost',
        ...createSortingHeader('Purchase Cost'),
        cell: ({ row }) => <div className="text-right">{formatCurrency(row.getValue('purchase_cost'))}</div>,
    },
    {
        accessorKey: 'salvage_value',
        header: () => <div className="text-right">Salvage Value</div>,
        cell: ({ row }) => <div className="text-right">{formatCurrency(row.getValue('salvage_value'))}</div>,
        enableSorting: false,
    },
    {
        accessorKey: 'useful_life_months',
        header: () => <div className="text-right">Useful Life (Mos)</div>,
        cell: ({ row }) => <div className="text-right">{row.getValue('useful_life_months')}</div>,
        enableSorting: false,
    },
    {
        accessorKey: 'accumulated_depreciation',
        ...createSortingHeader('Accum. Depreciation'),
        cell: ({ row }) => <div className="text-right">{formatCurrency(row.getValue('accumulated_depreciation'))}</div>,
    },
    {
        accessorKey: 'book_value',
        ...createSortingHeader('Book Value'),
        cell: ({ row }) => <div className="text-right font-medium">{formatCurrency(row.getValue('book_value'))}</div>,
    },
];
