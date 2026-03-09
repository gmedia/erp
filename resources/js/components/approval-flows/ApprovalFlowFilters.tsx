'use client';

import {
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';
import { APPROVABLE_TYPE_OPTIONS } from '@/constants/model-options';

const statusOptions = [
    { label: 'Active', value: '1' },
    { label: 'Inactive', value: '0' },
];

export function createApprovalFlowFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField('search', 'Search', 'Search by Code or Name...'),
        createSelectFilterField(
            'approvable_type',
            'Approvable Type',
            [...APPROVABLE_TYPE_OPTIONS],
            'All Types',
        ),
        createSelectFilterField(
            'is_active',
            'Status',
            statusOptions,
            'All Statuses',
        ),
    ];
}
