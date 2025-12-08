'use client';

import { createActionsColumn, createSelectColumn } from '@/components/common/BaseColumns';
import {
    createCurrencyColumn,
    createDateColumn,
    createEmailColumn,
    createPhoneColumn,
    createTextColumn,
} from '@/components/common/ColumnUtils';
import { Employee } from '@/types/employee';
import { ColumnDef } from '@tanstack/react-table';

export const employeeColumns: ColumnDef<Employee>[] = [
    createSelectColumn<Employee>(),
    createTextColumn<Employee>('name', 'Name'),
    createEmailColumn<Employee>('email', 'Email'),
    createPhoneColumn<Employee>('phone', 'Phone'),
    createTextColumn<Employee>('department', 'Department'),
    createTextColumn<Employee>('position', 'Position'),
    createCurrencyColumn<Employee>('salary', 'Salary'),
    createDateColumn<Employee>('hire_date', 'Hire Date'),
    createActionsColumn<Employee>({
        onEdit: () => {},
        onDelete: () => {},
    }),
];