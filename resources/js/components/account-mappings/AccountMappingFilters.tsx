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
            {
                key: 'page.account_mappings.search_placeholder',
                fallback: 'Search account mappings...',
            },
        ),
        createSelectFilterField(
            'type',
            { key: 'column.type', fallback: 'Type' },
            [
                {
                    label: {
                        key: 'filter.account_mapping_type_rename',
                        fallback: 'Rename',
                    },
                    value: 'rename',
                },
                {
                    label: {
                        key: 'filter.account_mapping_type_merge',
                        fallback: 'Merge',
                    },
                    value: 'merge',
                },
                {
                    label: {
                        key: 'filter.account_mapping_type_split',
                        fallback: 'Split',
                    },
                    value: 'split',
                },
            ],
            { key: 'filter.all_types', fallback: 'All Types' },
        ),
        createAsyncSelectFilterField(
            'source_coa_version_id',
            {
                key: 'column.source_coa_version',
                fallback: 'Source COA Version',
            },
            '/api/coa-versions',
            {
                key: 'filter.select_source_coa_version',
                fallback: 'Select source COA version',
            },
        ),
        createAsyncSelectFilterField(
            'target_coa_version_id',
            {
                key: 'column.target_coa_version',
                fallback: 'Target COA Version',
            },
            '/api/coa-versions',
            {
                key: 'filter.select_target_coa_version',
                fallback: 'Select target COA version',
            },
        ),
    ];
}
