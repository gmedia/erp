'use client';

import {
    createAsyncSelectFilterFields,
    createSelectFilterFields,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

/**
 * Creates filter fields for the product management module.
 */
export function createProductFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField('search', 'Search', 'Search code, name...'),
        ...createAsyncSelectFilterFields([
            {
                name: 'category_id',
                label: 'Category',
                url: '/api/product-categories',
                placeholder: 'Select category',
            },
            {
                name: 'unit_id',
                label: 'Unit',
                url: '/api/units',
                placeholder: 'Select unit',
            },
            {
                name: 'branch_id',
                label: 'Branch',
                url: '/api/branches',
                placeholder: 'Select branch',
            },
        ]),
        ...createSelectFilterFields([
            {
                name: 'type',
                label: 'Type',
                options: [
                    { value: 'raw_material', label: 'Raw Material' },
                    { value: 'work_in_progress', label: 'WIP' },
                    { value: 'finished_good', label: 'Finished Good' },
                    { value: 'purchased_good', label: 'Purchased Good' },
                    { value: 'service', label: 'Service' },
                ],
                placeholder: 'Select type',
            },
            {
                name: 'status',
                label: 'Status',
                options: [
                    { value: 'active', label: 'Active' },
                    { value: 'inactive', label: 'Inactive' },
                    { value: 'discontinued', label: 'Discontinued' },
                ],
                placeholder: 'Select status',
            },
        ]),
    ];
}
