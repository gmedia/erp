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
        createTextFilterField('search', 'Search', 'Search by Code or Name...'),
        createSelectFilterField(
            'approvable_type',
            'Approvable Type',
            [...APPROVABLE_TYPE_OPTIONS],
            'All Types',
        ),
        createBinaryStatusFilterField(
            'is_active',
            'Status',
            '1',
            '0',
            'All Statuses',
        ),
    ];
}
