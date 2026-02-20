'use client';

import {
    createAsyncSelectFilterField,
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

// Employee-specific filter fields
export function createEmployeeFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField('search', 'Search', 'Search employees...'),
        createAsyncSelectFilterField(
            'department_id',
            'Department',
            '/api/departments',
            'Select a department',
        ),
        createAsyncSelectFilterField(
            'position_id',
            'Position',
            '/api/positions',
            'Select a position',
        ),
        createAsyncSelectFilterField(
            'branch_id',
            'Branch',
            '/api/branches',
            'Select a branch',
        ),
        createSelectFilterField(
            'employment_status',
            'Status',
            [
                { label: 'Regular', value: 'regular' },
                { label: 'Intern', value: 'intern' },
            ],
            'Select status',
        ),
    ];
}
