'use client';

import {
    createEmployeeOrganizationFilterFields,
    createEmploymentStatusFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

// Employee-specific filter fields
export function createEmployeeFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField('search', 'Search', 'Search employees...'),
        ...createEmployeeOrganizationFilterFields(),
        createEmploymentStatusFilterField(),
    ];
}
