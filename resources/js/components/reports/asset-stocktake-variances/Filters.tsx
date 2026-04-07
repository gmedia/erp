import {
    createAssetStocktakeVarianceScopeFilterFields,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createVarianceFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            'Search',
            'Search code, name, notes...',
        ),
        ...createAssetStocktakeVarianceScopeFilterFields(),
    ];
}
