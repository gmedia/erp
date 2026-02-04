import { type FieldDescriptor } from '@/components/common/filters';

export function createAssetModelFilterFields(): FieldDescriptor[] {
    return [
        {
            name: 'search',
            label: 'Search',
            type: 'text',
            placeholder: 'Search by model name or manufacturer...',
        },
        {
            name: 'asset_category_id',
            label: 'Category',
            type: 'select-async',
            placeholder: 'All Categories',
            url: '/api/asset-categories',
        },
    ];
}
