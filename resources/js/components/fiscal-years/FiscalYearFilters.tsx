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
            {
                key: 'page.fiscal_years.search_placeholder',
                fallback: 'Search fiscal years...',
            },
        ),
        createFiscalYearStatusFilterField(),
    ];
}
