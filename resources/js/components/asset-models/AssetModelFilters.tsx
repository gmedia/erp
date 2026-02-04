import {
    createAsyncSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createAssetModelFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            'Search',
            'Search by model name or manufacturer...',
        ),
        createAsyncSelectFilterField(
            'asset_category_id',
            'Category',
            '/api/asset-categories',
            'All Categories',
        ),
    ];
}
