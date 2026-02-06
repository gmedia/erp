'use client';

import {
    createAsyncSelectFilterField,
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createAssetFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField('search', 'Search', 'Search assets...'),
        createAsyncSelectFilterField(
            'asset_category_id',
            'Category',
            '/api/asset-categories',
            'Select a category',
        ),
        createAsyncSelectFilterField(
            'branch_id',
            'Branch',
            '/api/branches',
            'Select a branch',
        ),
        createSelectFilterField(
            'status',
            'Status',
            [
                { value: 'draft', label: 'Draft' },
                { value: 'active', label: 'Active' },
                { value: 'maintenance', label: 'Maintenance' },
                { value: 'disposed', label: 'Disposed' },
                { value: 'lost', label: 'Lost' },
            ],
            'Select a status',
        ),
        createSelectFilterField(
            'condition',
            'Condition',
            [
                { value: 'good', label: 'Good' },
                { value: 'needs_repair', label: 'Needs Repair' },
                { value: 'damaged', label: 'Damaged' },
            ],
            'Select a condition',
        ),
    ];
}
