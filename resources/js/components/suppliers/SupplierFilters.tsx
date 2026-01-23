'use client';

import {
    createAsyncSelectFilterField,
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

// Supplier-specific filter fields
export function createSupplierFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField('search', 'Search', 'Search suppliers...'),
        createAsyncSelectFilterField(
            'branch_id',
            'Branch',
            '/api/branches',
            'Select a branch',
        ),
        createSelectFilterField(
            'category',
            'Category',
            [
                { value: 'electronics', label: 'Electronics' },
                { value: 'furniture', label: 'Furniture' },
                { value: 'stationery', label: 'Stationery' },
                { value: 'services', label: 'Services' },
                { value: 'other', label: 'Other' },
            ],
            'Select Category',
        ),
        createSelectFilterField(
            'status',
            'Status',
            [
                { value: 'active', label: 'Active' },
                { value: 'inactive', label: 'Inactive' },
            ],
            'Select Status',
        ),
    ];
}
