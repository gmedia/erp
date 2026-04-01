'use client';

import {
    createAsyncSelectFilterFields,
    createSelectFilterFields,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createAssetFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField('search', 'Search', 'Search assets...'),
        ...createAsyncSelectFilterFields([
            {
                name: 'asset_category_id',
                label: 'Category',
                url: '/api/asset-categories',
                placeholder: 'Select a category',
            },
            {
                name: 'asset_location_id',
                label: 'Location',
                url: '/api/asset-locations',
                placeholder: 'Select a location',
            },
            {
                name: 'department_id',
                label: 'Department',
                url: '/api/departments',
                placeholder: 'Select a department',
            },
            {
                name: 'employee_id',
                label: 'Employee',
                url: '/api/employees',
                placeholder: 'Select an employee',
            },
            {
                name: 'supplier_id',
                label: 'Supplier',
                url: '/api/suppliers',
                placeholder: 'Select a supplier',
            },
            {
                name: 'branch_id',
                label: 'Branch',
                url: '/api/branches',
                placeholder: 'Select a branch',
            },
        ]),
        ...createSelectFilterFields([
            {
                name: 'status',
                label: 'Status',
                options: [
                    { value: 'draft', label: 'Draft' },
                    { value: 'active', label: 'Active' },
                    { value: 'maintenance', label: 'Maintenance' },
                    { value: 'disposed', label: 'Disposed' },
                    { value: 'lost', label: 'Lost' },
                ],
                placeholder: 'Select a status',
            },
            {
                name: 'condition',
                label: 'Condition',
                options: [
                    { value: 'good', label: 'Good' },
                    { value: 'needs_repair', label: 'Needs Repair' },
                    { value: 'damaged', label: 'Damaged' },
                ],
                placeholder: 'Select a condition',
            },
        ]),
    ];
}
