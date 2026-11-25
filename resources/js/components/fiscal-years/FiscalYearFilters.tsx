'use client';

import {
    createFiscalYearStatusFilterField,
    createTextFilterField,
    FieldDescriptor,
} from '@/components/common/filters';

export function createFiscalYearFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'column.search', fallback: 'Search' },
            'Search fiscal years...',
        ),
        createFiscalYearStatusFilterField(),
    ];
}
