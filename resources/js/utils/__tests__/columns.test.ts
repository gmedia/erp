// Type checking test for column utilities
import { Department } from '@/types/department';
import { Employee } from '@/types/employee';
import { Position } from '@/types/position';
import { describe, expect, it } from 'vitest';
import {
    createActionsColumn,
    createCurrencyColumn,
    createDateColumn,
    createEmailColumn,
    createPhoneColumn,
    createSelectColumn,
    createTextColumn,
} from '../columns';

describe('createSelectColumn', () => {
    it('creates a select column with correct id', () => {
        const col = createSelectColumn<Department>();
        expect(col.id).toBe('select');
    });

    it('creates a select column with meta', () => {
        const col = createSelectColumn<Department>();
        expect(col.meta).toEqual({ enableColumnFilter: false });
    });
});

describe('createTextColumn', () => {
    it('creates a text column with given accessor key and label', () => {
        const col = createTextColumn<Department>({
            accessorKey: 'name',
            label: 'Name',
        });
        expect(col.accessorKey).toBe('name');
        expect(col.header).toBe('Name');
    });
});

describe('createDateColumn', () => {
    it('creates a date column with default format', () => {
        const col = createDateColumn<Department>({
            accessorKey: 'created_at',
            label: 'Created At',
        });
        expect(col.accessorKey).toBe('created_at');
        expect(col.header).toBe('Created At');
    });

    it('creates a date column with sorting disabled', () => {
        const col = createDateColumn<Position>({
            accessorKey: 'updated_at',
            label: 'Updated At',
            enableSorting: false,
        });
        expect(col.enableSorting).toBe(false);
    });
});

describe('createEmailColumn', () => {
    it('creates an email column with correct accessor key', () => {
        const col = createEmailColumn<Employee>({
            accessorKey: 'email',
            label: 'Email',
        });
        expect(col.accessorKey).toBe('email');
        expect(col.header).toBe('Email');
    });
});

describe('createPhoneColumn', () => {
    it('creates a phone column with correct accessor key', () => {
        const col = createPhoneColumn<Employee>({
            accessorKey: 'phone',
            label: 'Phone',
        });
        expect(col.accessorKey).toBe('phone');
    });
});

describe('createCurrencyColumn', () => {
    it('creates a currency column with correct accessor key', () => {
        const col = createCurrencyColumn<Employee>({
            accessorKey: 'salary',
            label: 'Salary',
        });
        expect(col.accessorKey).toBe('salary');
    });
});

describe('createActionsColumn', () => {
    it('creates an actions column with id', () => {
        const col = createActionsColumn<Department>();
        expect(col.id).toBe('actions');
    });

    it('creates an actions column with callbacks', () => {
        const col = createActionsColumn<Department>({
            onEdit: () => {},
            onDelete: () => {},
        });
        expect(col.id).toBe('actions');
    });
});

describe('column type safety', () => {
    it('builds Department columns array', () => {
        const columns = [
            createSelectColumn<Department>(),
            createTextColumn<Department>({
                accessorKey: 'name',
                label: 'Name',
            }),
            createDateColumn<Department>({
                accessorKey: 'created_at',
                label: 'Created At',
            }),
            createActionsColumn<Department>({
                onEdit: () => {},
                onDelete: () => {},
            }),
        ];
        expect(columns).toHaveLength(4);
    });

    it('builds Employee columns array', () => {
        const columns = [
            createSelectColumn<Employee>(),
            createTextColumn<Employee>({ accessorKey: 'name', label: 'Name' }),
            createEmailColumn<Employee>({
                accessorKey: 'email',
                label: 'Email',
            }),
            createPhoneColumn<Employee>({
                accessorKey: 'phone',
                label: 'Phone',
            }),
            createCurrencyColumn<Employee>({
                accessorKey: 'salary',
                label: 'Salary',
            }),
            createDateColumn<Employee>({
                accessorKey: 'hire_date',
                label: 'Hire Date',
            }),
            createActionsColumn<Employee>({
                onEdit: () => {},
                onDelete: () => {},
                onView: () => {},
            }),
        ];
        expect(columns).toHaveLength(7);
    });

    it('builds Position columns array', () => {
        const columns = [
            createSelectColumn<Position>(),
            createTextColumn<Position>({ accessorKey: 'name', label: 'Name' }),
            createDateColumn<Position>({
                accessorKey: 'updated_at',
                label: 'Updated At',
                enableSorting: false,
            }),
            createActionsColumn<Position>({
                onEdit: () => {},
                onDelete: () => {},
            }),
        ];
        expect(columns).toHaveLength(4);
    });
});
