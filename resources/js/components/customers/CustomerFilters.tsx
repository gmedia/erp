'use client';

import {
    createAsyncSelectFilterField,
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

const customerTypeOptions = [
    { value: 'individual', label: 'Individual' },
    { value: 'company', label: 'Company' },
];

const statusOptions = [
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
];

// Customer-specific filter fields
export function createCustomerFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField('search', 'Search', 'Search customers...'),
        createAsyncSelectFilterField(
            'branch',
            'Branch',
            '/api/branches',
            'Select a branch',
        ),
        createSelectFilterField(
            'customer_type',
            'Customer Type',
            customerTypeOptions,
            'All types',
        ),
        createSelectFilterField('status', 'Status', statusOptions, 'All statuses'),
    ];
}
