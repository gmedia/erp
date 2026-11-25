import {
    createAsyncSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createAssetLocationFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'column.search', fallback: 'Search' },
            'Search asset locations...',
        ),
        createAsyncSelectFilterField(
            'branch_id',
            { key: 'column.branch', fallback: 'Branch' },
            '/api/branches',
            'All Branches',
        ),
        createAsyncSelectFilterField(
            'parent_id',
            { key: 'column.parent_location', fallback: 'Parent Location' },
            '/api/asset-locations',
            'All Locations',
        ),
    ];
}
