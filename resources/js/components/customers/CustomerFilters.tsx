'use client';

import {
    createAsyncSelectFilterField,
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

const statusOptions = [
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
];

// Customer-specific filter fields
export function createCustomerFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField('search', 'Search', 'Search customers...'),
        createAsyncSelectFilterField(
            'branch_id',
            'Branch',
            '/api/branches',
            'Select a branch',
        ),
        createAsyncSelectFilterField(
            'category_id',
            'Category',
            '/api/customer-categories',
            'Select Category',
        ),
        createSelectFilterField('status', 'Status', statusOptions, 'All statuses'),
    ];
}
