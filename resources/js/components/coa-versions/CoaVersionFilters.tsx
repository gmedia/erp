'use client';

import { createSelectFilterField, createTextFilterField, FieldDescriptor } from '@/components/common/filters';

export function createCoaVersionFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField('search', 'Search', 'Search COA versions...'),
        createSelectFilterField(
            'status',
            'Status',
            [
                { label: 'Draft', value: 'draft' },
                { label: 'Active', value: 'active' },
                { label: 'Archived', value: 'archived' },
            ],
            'All Statuses'
        ),
        // Note: Fiscal year list should ideally be dynamic, but for simple CRUD pattern it's often passed via props or fetched.
        // For now we'll stick to the standard search and status. 
        // If needed, we can add fiscal_year_id filter with dynamic data later.
    ];
}
