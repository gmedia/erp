'use client';

import { FilterDatePicker } from '@/components/common/FilterDatePicker';
import {
    createAsyncSelectFilterField,
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createPurchaseRequestFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            'Search',
            'Search PR number, notes, or rejection reason...',
        ),
        createAsyncSelectFilterField(
            'branch_id',
            'Branch',
            '/api/branches',
            'Select a branch',
        ),
        createAsyncSelectFilterField(
            'department_id',
            'Department',
            '/api/departments',
            'Select a department',
        ),
        createAsyncSelectFilterField(
            'requested_by',
            'Requester',
            '/api/employees',
            'Select requester',
        ),
        createSelectFilterField(
            'priority',
            'Priority',
            [
                { value: 'low', label: 'Low' },
                { value: 'normal', label: 'Normal' },
                { value: 'high', label: 'High' },
                { value: 'urgent', label: 'Urgent' },
            ],
            'Select Priority',
        ),
        createSelectFilterField(
            'status',
            'Status',
            [
                { value: 'draft', label: 'Draft' },
                { value: 'pending_approval', label: 'Pending Approval' },
                { value: 'approved', label: 'Approved' },
                { value: 'rejected', label: 'Rejected' },
                { value: 'partially_ordered', label: 'Partially Ordered' },
                { value: 'fully_ordered', label: 'Fully Ordered' },
                { value: 'cancelled', label: 'Cancelled' },
            ],
            'Select Status',
        ),
        {
            name: 'request_date_from',
            label: 'Request Date From',
            component: <FilterDatePicker placeholder="Request Date From" />,
        },
        {
            name: 'request_date_to',
            label: 'Request Date To',
            component: <FilterDatePicker placeholder="Request Date To" />,
        },
    ];
}
