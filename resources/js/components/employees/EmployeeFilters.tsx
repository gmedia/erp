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
        createTextFilterField(
            'search',
            { key: 'column.search', fallback: 'Search' },
            {
                key: 'page.employees.search_placeholder',
                fallback: 'Search employees...',
            },
        ),
        ...createEmployeeOrganizationFilterFields(),
        createEmploymentStatusFilterField(),
    ];
}
