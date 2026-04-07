import { createReportTextColumn } from '@/components/common/ReportColumns';
import { formatCurrency } from '@/lib/utils';
import { createSortingHeader } from '@/utils/columns';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import { ColumnDef } from '@tanstack/react-table';

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
}
export const bookValueDepreciationColumns: ColumnDef<BookValueDepreciationReportItem>[] =
    [
        createReportTextColumn<BookValueDepreciationReportItem>({
            accessorKey: 'asset_code',
            header: 'Asset Code',
            getValue: (item) => item.asset_code,
            className: 'font-medium',
            sortable: true,
        }),
        createReportTextColumn<BookValueDepreciationReportItem>({
            accessorKey: 'name',
            header: 'Asset Name',
            getValue: (item) => item.name,
            sortable: true,
        }),
        createReportTextColumn<BookValueDepreciationReportItem>({
            accessorKey: 'category_name',
            header: 'Category',
            getValue: (item) => item.category_name,
        }),
        createReportTextColumn<BookValueDepreciationReportItem>({
            accessorKey: 'branch_name',
            header: 'Branch',
            getValue: (item) => item.branch_name,
        }),
        createReportTextColumn<BookValueDepreciationReportItem>({
            accessorKey: 'purchase_date',
            header: 'Purchase Date',
            getValue: (item) => formatDateByRegionalSettings(item.purchase_date),
            sortable: true,
        }),
        {
            accessorKey: 'purchase_cost',
            ...createSortingHeader('Purchase Cost'),
            cell: ({ row }) => (
                <div className="text-right">
                    {formatCurrency(row.getValue('purchase_cost'))}
                </div>
            ),
        },
        {
            accessorKey: 'salvage_value',
            header: () => <div className="text-right">Salvage Value</div>,
            cell: ({ row }) => (
                <div className="text-right">
                    {formatCurrency(row.getValue('salvage_value'))}
                </div>
            ),
            enableSorting: false,
        },
        {
            accessorKey: 'useful_life_months',
            header: () => <div className="text-right">Useful Life (Mos)</div>,
            cell: ({ row }) => (
                <div className="text-right">
                    {row.getValue('useful_life_months')}
                </div>
            ),
            enableSorting: false,
        },
        {
            accessorKey: 'accumulated_depreciation',
            ...createSortingHeader('Accum. Depreciation'),
            cell: ({ row }) => (
                <div className="text-right">
                    {formatCurrency(row.getValue('accumulated_depreciation'))}
                </div>
            ),
        },
        {
            accessorKey: 'book_value',
            ...createSortingHeader('Book Value'),
            cell: ({ row }) => (
                <div className="text-right font-medium">
                    {formatCurrency(row.getValue('book_value'))}
                </div>
            ),
        },
    ];
