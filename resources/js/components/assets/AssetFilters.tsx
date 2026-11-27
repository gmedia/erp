'use client';

import {
    createAssetStatusConditionFilterFields,
    createAsyncSelectFilterFields,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createAssetFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'column.search', fallback: 'Search' },
            {
                key: 'page.assets.search_placeholder',
                fallback: 'Search assets...',
            },
        ),
        ...createAsyncSelectFilterFields([
            {
                name: 'asset_category_id',
                label: { key: 'column.category', fallback: 'Category' },
                url: '/api/asset-categories',
                placeholder: {
                    key: 'filter.select_category',
                    fallback: 'Select a category',
                },
            },
            {
                name: 'asset_location_id',
                label: { key: 'column.location', fallback: 'Location' },
                url: '/api/asset-locations',
                placeholder: {
                    key: 'filter.select_location',
                    fallback: 'Select a location',
                },
            },
            {
                name: 'department_id',
                label: { key: 'column.department', fallback: 'Department' },
                url: '/api/departments',
                placeholder: {
                    key: 'filter.select_department',
                    fallback: 'Select a department',
                },
            },
            {
                name: 'employee_id',
                label: { key: 'column.employee', fallback: 'Employee' },
                url: '/api/employees',
                placeholder: {
                    key: 'filter.select_employee',
                    fallback: 'Select an employee',
                },
            },
            {
                name: 'supplier_id',
                label: { key: 'column.supplier', fallback: 'Supplier' },
                url: '/api/suppliers',
                placeholder: {
                    key: 'filter.select_supplier',
                    fallback: 'Select a supplier',
                },
            },
            {
                name: 'branch_id',
                label: { key: 'column.branch', fallback: 'Branch' },
                url: '/api/branches',
                placeholder: {
                    key: 'filter.select_branch',
                    fallback: 'Select a branch',
                },
            },
        ]),
        ...createAssetStatusConditionFilterFields(),
    ];
}
