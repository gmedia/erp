'use client';

import { createSelectFilterField, createTextFilterField, FieldDescriptor } from '@/components/common/filters';

export function createFiscalYearFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField('search', 'Search', 'Search fiscal years...'),
        createSelectFilterField(
            'status',
            'Status',
            [
                { label: 'Open', value: 'open' },
                { label: 'Closed', value: 'closed' },
                { label: 'Locked', value: 'locked' },
            ],
            'All Statuses'
        ),
    ];
}
