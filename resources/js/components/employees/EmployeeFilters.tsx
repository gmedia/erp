'use client';

import { createSelectFilterField, createTextFilterField, type FieldDescriptor } from '@/components/common/filters';

import { DEPARTMENTS, POSITIONS } from '@/constants';

// Employee-specific filter fields
export function createEmployeeFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField('search', 'Search', 'Search employees...'),
        createSelectFilterField('department', 'Department', DEPARTMENTS, 'Select a department'),
        createSelectFilterField('position', 'Position', POSITIONS, 'Select a position'),
    ];
}
