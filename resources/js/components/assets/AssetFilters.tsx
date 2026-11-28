'use client';

import {
    createAssetStatusConditionFilterFields,
    createAsyncLookupFilterFields,
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
        ...createAsyncLookupFilterFields([
            ['asset_category_id', 'category', '/api/asset-categories'],
            ['asset_location_id', 'location', '/api/asset-locations'],
            ['department_id', 'department', '/api/departments'],
            ['employee_id', 'employee', '/api/employees'],
            ['supplier_id', 'supplier', '/api/suppliers'],
            ['branch_id', 'branch', '/api/branches'],
        ]),
        ...createAssetStatusConditionFilterFields(),
    ];
}
