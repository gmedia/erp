import {
    createAssetCategoryFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createAssetModelFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'column.search', fallback: 'Search' },
            {
                key: 'page.asset_models.search_placeholder',
                fallback: 'Search by model name or manufacturer...',
            },
        ),
        createAssetCategoryFilterField({
            key: 'filter.all_categories',
            fallback: 'All Categories',
        }),
    ];
}
