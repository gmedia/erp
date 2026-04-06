import {
    createAssetCategoryFilterField,
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
        createAssetCategoryFilterField('All Categories'),
    ];
}
