'use client';

import {
    createAsyncSelectFilterField,
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createAccountMappingFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'column.search', fallback: 'Search' },
            'Search account mappings...',
        ),
        createSelectFilterField(
            'type',
            { key: 'column.type', fallback: 'Type' },
            [
                { label: 'Rename', value: 'rename' },
                { label: 'Merge', value: 'merge' },
                { label: 'Split', value: 'split' },
            ],
            'All Types',
        ),
        createAsyncSelectFilterField(
            'source_coa_version_id',
            {
                key: 'column.source_coa_version',
                fallback: 'Source COA Version',
            },
            '/api/coa-versions',
            'Select source COA version',
        ),
        createAsyncSelectFilterField(
            'target_coa_version_id',
            {
                key: 'column.target_coa_version',
                fallback: 'Target COA Version',
            },
            '/api/coa-versions',
            'Select target COA version',
        ),
    ];
}
