'use client';

import {
    createAsyncSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

// Employee-specific filter fields
export function createEmployeeFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField('search', 'Search', 'Search employees...'),
        createAsyncSelectFilterField(
            'department',
            'Department',
            '/api/departments',
            'Select a department',
        ),
        createAsyncSelectFilterField(
            'position',
            'Position',
            '/api/positions',
            'Select a position',
        ),
    ];
}
