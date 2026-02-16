import {
    createAsyncSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createAssetLocationFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            'Search',
            'Search asset locations...',
        ),
        createAsyncSelectFilterField(
            'branch_id',
            'Branch',
            '/api/branches',
            'All Branches',
        ),
        createAsyncSelectFilterField(
            'parent_id',
            'Parent Location',
            '/api/asset-locations',
            'All Locations',
        ),
    ];
}
