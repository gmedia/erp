'use client';

import { LabelText } from '@/components/common/TranslatedText';
import { Badge } from '@/components/ui/badge';
import { statusBadgeVariant } from '@/lib/status-badge';
import { Employee } from '@/types/entity';
import {
    createActionsColumn,
    createEmailColumn,
    createPhoneColumn,
    createSelectColumn,
    createSortingHeader,
    createTextColumn,
} from '@/utils/columns';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import { FORMAT_LOCALE } from '@/utils/formatters';
import { formatCurrencyByRegionalSettings } from '@/utils/number-format';
import { ColumnDef } from '@tanstack/react-table';

export const employeeColumns: ColumnDef<Employee>[] = [
    createSelectColumn<Employee>(),
    {
        id: 'employee_id',
        accessorKey: 'employee_id',
        ...createSortingHeader({ key: 'column.nik', fallback: 'NIK' }),
        cell: ({ row }) => <div>{row.original.employee_id}</div>,
    },
    createTextColumn<Employee>({
        accessorKey: 'name',
        label: { key: 'column.name', fallback: 'Name' },
        primary: true,
        description: (row) => row.current_employment?.position?.name ?? null,
    }),
    {
        id: 'employments.employment_status',
        accessorFn: (row) => row.current_employment?.employment_status,
        ...createSortingHeader({ key: 'column.status', fallback: 'Status' }),
        cell: ({ row }) => {
            const status = row.original.current_employment?.employment_status;
            if (!status) {
                return <div>-</div>;
            }

            const label =
                status === 'intern'
                    ? { key: 'status.intern', fallback: 'Intern' }
                    : { key: 'status.regular', fallback: 'Regular' };
            return (
                <Badge variant={statusBadgeVariant(status)}>
                    <LabelText label={label} />
                </Badge>
            );
        },
    },
    createEmailColumn<Employee>({
        accessorKey: 'email',
        label: { key: 'column.email', fallback: 'Email' },
    }),
    createPhoneColumn<Employee>({
        accessorKey: 'phone',
        label: { key: 'column.phone', fallback: 'Phone' },
    }),
    {
        id: 'employments.department_id',
        accessorFn: (row) => row.current_employment?.department?.name,
        ...createSortingHeader({
            key: 'column.department',
            fallback: 'Department',
        }),
        cell: ({ row }) => (
            <div>
                {row.original.current_employment?.department?.name ?? '-'}
            </div>
        ),
    },
    {
        id: 'employments.position_id',
        accessorFn: (row) => row.current_employment?.position?.name,
        ...createSortingHeader({
            key: 'column.position',
            fallback: 'Position',
        }),
        cell: ({ row }) => (
            <div>{row.original.current_employment?.position?.name ?? '-'}</div>
        ),
    },
    {
        id: 'employments.branch_id',
        accessorFn: (row) => row.current_employment?.branch?.name,
        ...createSortingHeader({ key: 'column.branch', fallback: 'Branch' }),
        cell: ({ row }) => (
            <div>{row.original.current_employment?.branch?.name ?? '-'}</div>
        ),
    },
    {
        id: 'employments.salary',
        accessorFn: (row) => row.current_employment?.salary,
        ...createSortingHeader(
            { key: 'column.salary', fallback: 'Salary' },
            { align: 'right' },
        ),
        cell: ({ row }) => (
            <div className="text-right tabular-nums">
                {formatCurrencyByRegionalSettings(
                    row.original.current_employment?.salary ?? '',
                    { locale: FORMAT_LOCALE },
                )}
            </div>
        ),
    },
    {
        id: 'employments.hire_date',
        accessorFn: (row) => row.current_employment?.hire_date,
        ...createSortingHeader({
            key: 'column.hire_date',
            fallback: 'Hire Date',
        }),
        cell: ({ row }) => (
            <div>
                {formatDateByRegionalSettings(
                    row.original.current_employment?.hire_date,
                )}
            </div>
        ),
    },
    createActionsColumn<Employee>(),
];
