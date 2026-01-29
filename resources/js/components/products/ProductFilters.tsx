'use client';

import {
    createAsyncSelectFilterField,
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

/**
 * Creates filter fields for the product management module.
 */
export function createProductFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField('search', 'Search', 'Search code, name...'),
        createAsyncSelectFilterField(
            'category_id',
            'Category',
            '/api/product-categories',
            'Select category',
        ),
        createAsyncSelectFilterField(
            'unit_id',
            'Unit',
            '/api/units',
            'Select unit',
        ),
        createAsyncSelectFilterField(
            'branch_id',
            'Branch',
            '/api/branches',
            'Select branch',
        ),
        createSelectFilterField(
            'type',
            'Type',
            [
                { value: 'raw_material', label: 'Raw Material' },
                { value: 'work_in_progress', label: 'WIP' },
                { value: 'finished_good', label: 'Finished Good' },
                { value: 'purchased_good', label: 'Purchased Good' },
                { value: 'service', label: 'Service' },
            ],
            'Select type',
        ),
        createSelectFilterField(
            'status',
            'Status',
            [
                { value: 'active', label: 'Active' },
                { value: 'inactive', label: 'Inactive' },
                { value: 'discontinued', label: 'Discontinued' },
            ],
            'Select status',
        ),
    ];
}
