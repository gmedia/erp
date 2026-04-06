'use client';

import {
    createAsyncSelectFilterFields,
    createAssetStatusConditionFilterFields,
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
        ...createAssetStatusConditionFilterFields(),
    ];
}
