'use client';

import {
    createFiscalYearStatusFilterField,
    createTextFilterField,
    FieldDescriptor,
} from '@/components/common/filters';

export function createFiscalYearFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField('search', 'Search', 'Search fiscal years...'),
        createFiscalYearStatusFilterField(),
    ];
}
