'use client';

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
import { formatCurrencyByRegionalSettings } from '@/utils/number-format';
import { ColumnDef } from '@tanstack/react-table';

export const employeeColumns: ColumnDef<Employee>[] = [
    createSelectColumn<Employee>(),
    {
        id: 'employee_id',
        accessorKey: 'employee_id',
        ...createSortingHeader('NIK'),
        cell: ({ row }) => <div>{row.original.employee_id}</div>,
    },
    createTextColumn<Employee>({
        accessorKey: 'name',
        label: 'Name',
        primary: true,
    }),
    {
        id: 'employments.employment_status',
        accessorFn: (row) => row.current_employment?.employment_status,
        ...createSortingHeader('Status'),
        cell: ({ row }) => {
            const status = row.original.current_employment?.employment_status;
            if (!status) {
                return <div>-</div>;
            }

            const label = status === 'intern' ? 'Intern' : 'Regular';
            return <Badge variant={statusBadgeVariant(status)}>{label}</Badge>;
        },
    },
    createEmailColumn<Employee>({ accessorKey: 'email', label: 'Email' }),
    createPhoneColumn<Employee>({ accessorKey: 'phone', label: 'Phone' }),
    {
        id: 'employments.department_id',
        accessorFn: (row) => row.current_employment?.department?.name,
        ...createSortingHeader('Department'),
        cell: ({ row }) => (
            <div>
                {row.original.current_employment?.department?.name ?? '-'}
            </div>
        ),
    },
    {
        id: 'employments.position_id',
        accessorFn: (row) => row.current_employment?.position?.name,
        ...createSortingHeader('Position'),
        cell: ({ row }) => (
            <div>{row.original.current_employment?.position?.name ?? '-'}</div>
        ),
    },
    {
        id: 'employments.branch_id',
        accessorFn: (row) => row.current_employment?.branch?.name,
        ...createSortingHeader('Branch'),
        cell: ({ row }) => (
            <div>{row.original.current_employment?.branch?.name ?? '-'}</div>
        ),
    },
    {
        id: 'employments.salary',
        accessorFn: (row) => row.current_employment?.salary,
        ...createSortingHeader('Salary'),
        cell: ({ row }) => (
            <div className="text-right tabular-nums">
                {formatCurrencyByRegionalSettings(
                    row.original.current_employment?.salary ?? '',
                    { locale: 'id-ID', currency: 'IDR' },
                )}
            </div>
        ),
    },
    {
        id: 'employments.hire_date',
        accessorFn: (row) => row.current_employment?.hire_date,
        ...createSortingHeader('Hire Date'),
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
