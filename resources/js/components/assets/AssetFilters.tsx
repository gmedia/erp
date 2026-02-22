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
            'asset_location_id',
            'Location',
            '/api/asset-locations',
            'Select a location',
        ),
        createAsyncSelectFilterField(
            'department_id',
            'Department',
            '/api/departments',
            'Select a department',
        ),
        createAsyncSelectFilterField(
            'employee_id',
            'Employee',
            '/api/employees',
            'Select an employee',
        ),
        createAsyncSelectFilterField(
            'supplier_id',
            'Supplier',
            '/api/suppliers',
            'Select a supplier',
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
