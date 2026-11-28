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
            {
                key: 'page.asset_locations.search_placeholder',
                fallback: 'Search asset locations...',
            },
        ),
        createAsyncSelectFilterField(
            'branch_id',
            { key: 'column.branch', fallback: 'Branch' },
            '/api/branches',
            { key: 'filter.all_branches', fallback: 'All Branches' },
        ),
        createAsyncSelectFilterField(
            'parent_id',
            { key: 'column.parent_location', fallback: 'Parent Location' },
            '/api/asset-locations',
            { key: 'filter.all_locations', fallback: 'All Locations' },
        ),
    ];
}
