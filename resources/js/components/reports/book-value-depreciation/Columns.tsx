import { createReportTextColumn } from '@/components/common/ReportColumns';
import { LabelText } from '@/components/common/TranslatedText';
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
            header: { key: 'column.asset_code', fallback: 'Asset Code' },
            getValue: (item) => item.asset_code,
            className: 'font-medium',
            sortable: true,
        }),
        createReportTextColumn<BookValueDepreciationReportItem>({
            accessorKey: 'name',
            header: { key: 'column.asset_name', fallback: 'Asset Name' },
            getValue: (item) => item.name,
            sortable: true,
        }),
        createReportTextColumn<BookValueDepreciationReportItem>({
            accessorKey: 'category_name',
            header: { key: 'column.category', fallback: 'Category' },
            getValue: (item) => item.category_name,
        }),
        createReportTextColumn<BookValueDepreciationReportItem>({
            accessorKey: 'branch_name',
            header: { key: 'column.branch', fallback: 'Branch' },
            getValue: (item) => item.branch_name,
        }),
        createReportTextColumn<BookValueDepreciationReportItem>({
            accessorKey: 'purchase_date',
            header: { key: 'column.purchase_date', fallback: 'Purchase Date' },
            getValue: (item) =>
                formatDateByRegionalSettings(item.purchase_date),
            sortable: true,
        }),
        {
            accessorKey: 'purchase_cost',
            meta: { numeric: true },
            ...createSortingHeader(
                {
                    key: 'column.asset_purchase_cost',
                    fallback: 'Purchase Cost',
                },
                { align: 'right' },
            ),
            cell: ({ row }) => (
                <div className="text-right">
                    {formatCurrency(row.getValue('purchase_cost'))}
                </div>
            ),
        },
        {
            accessorKey: 'salvage_value',
            header: () => (
                <div className="text-right">
                    <LabelText
                        label={{
                            key: 'column.salvage_value',
                            fallback: 'Salvage Value',
                        }}
                    />
                </div>
            ),
            cell: ({ row }) => (
                <div className="text-right">
                    {formatCurrency(row.getValue('salvage_value'))}
                </div>
            ),
            enableSorting: false,
        },
        {
            accessorKey: 'useful_life_months',
            header: () => (
                <div className="text-right">
                    <LabelText
                        label={{
                            key: 'column.useful_life_months',
                            fallback: 'Useful Life (Mos)',
                        }}
                    />
                </div>
            ),
            cell: ({ row }) => (
                <div className="text-right">
                    {row.getValue('useful_life_months')}
                </div>
            ),
            enableSorting: false,
        },
        {
            accessorKey: 'accumulated_depreciation',
            meta: { numeric: true },
            ...createSortingHeader(
                {
                    key: 'column.accumulated_depreciation',
                    fallback: 'Accum. Depreciation',
                },
                { align: 'right' },
            ),
            cell: ({ row }) => (
                <div className="text-right">
                    {formatCurrency(row.getValue('accumulated_depreciation'))}
                </div>
            ),
        },
        {
            accessorKey: 'book_value',
            meta: { numeric: true },
            ...createSortingHeader(
                {
                    key: 'column.book_value',
                    fallback: 'Book Value',
                },
                { align: 'right' },
            ),
            cell: ({ row }) => (
                <div className="text-right font-medium">
                    {formatCurrency(row.getValue('book_value'))}
                </div>
            ),
        },
    ];
