'use client';

import { Badge } from '@/components/ui/badge';
import { type ReportConfiguration } from '@/types/report-configuration';
import {
    createActionsColumn,
    createSelectColumn,
    createSortingHeader,
    createTextColumn,
} from '@/utils/columns';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import { type ColumnDef } from '@tanstack/react-table';

const reportTypeLabels: Record<string, string> = {
    balance_sheet: 'Balance Sheet',
    income_statement: 'Income Statement',
    cash_flow: 'Cash Flow',
    trial_balance: 'Trial Balance',
    custom: 'Custom',
};

export const reportConfigurationColumns: ColumnDef<ReportConfiguration>[] = [
    createSelectColumn<ReportConfiguration>(),
    createTextColumn<ReportConfiguration>({
        accessorKey: 'code',
        label: 'Code',
        enableSorting: true,
    }),
    createTextColumn<ReportConfiguration>({
        accessorKey: 'name',
        label: 'Name',
        enableSorting: true,
    }),
    {
        accessorKey: 'report_type',
        ...createSortingHeader('Report Type'),
        cell: ({ row }) => {
            const value = row.getValue('report_type') as string;
            return reportTypeLabels[value] ?? value;
        },
    },
    {
        accessorKey: 'sections',
        header: 'Sections',
        cell: ({ row }) => {
            const sections = row.original.sections ?? [];
            return <span>{sections.length}</span>;
        },
    },
    {
        accessorKey: 'is_active',
        ...createSortingHeader('Status'),
        cell: ({ row }) => (
            <Badge
                variant={row.getValue('is_active') ? 'default' : 'secondary'}
            >
                {row.getValue('is_active') ? 'Active' : 'Inactive'}
            </Badge>
        ),
    },
    {
        accessorKey: 'created_at',
        ...createSortingHeader('Created At'),
        cell: ({ row }) =>
            formatDateByRegionalSettings(row.getValue('created_at') as string),
    },
    createActionsColumn<ReportConfiguration>(),
];
