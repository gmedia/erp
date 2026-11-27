'use client';

import {
    createBinaryStatusFilterField,
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';
import { APPROVABLE_TYPE_OPTIONS } from '@/constants/model-options';

export function createApprovalFlowFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'filter_field.search', fallback: 'Search' },
            {
                key: 'page.approval_flows.search_placeholder',
                fallback: 'Search by Code or Name...',
            },
        ),
        createSelectFilterField(
            'approvable_type',
            {
                key: 'filter_field.approvable_type',
                fallback: 'Approvable Type',
            },
            [...APPROVABLE_TYPE_OPTIONS],
            { key: 'filter.all_types', fallback: 'All Types' },
        ),
        createBinaryStatusFilterField(
            'is_active',
            { key: 'filter_field.status', fallback: 'Status' },
            '1',
            '0',
            { key: 'filter_field.all_statuses', fallback: 'All Statuses' },
        ),
    ];
}
