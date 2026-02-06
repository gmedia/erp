'use client';

import {
    createAsyncSelectFilterField,
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createAccountMappingFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField('search', 'Search', 'Search account mappings...'),
        createSelectFilterField(
            'type',
            'Type',
            [
                { label: 'Rename', value: 'rename' },
                { label: 'Merge', value: 'merge' },
                { label: 'Split', value: 'split' },
            ],
            'All Types',
        ),
        createAsyncSelectFilterField(
            'source_coa_version_id',
            'Source COA Version',
            '/api/coa-versions',
            'Select source COA version',
        ),
        createAsyncSelectFilterField(
            'target_coa_version_id',
            'Target COA Version',
            '/api/coa-versions',
            'Select target COA version',
        ),
    ];
}
