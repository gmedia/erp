import {
    createAssetStocktakeVarianceScopeFilterFields,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createVarianceFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField('search', 'Search', {
            key: 'page.asset_stocktake_variances.search_placeholder',
            fallback: 'Search code, name, notes...',
        }),
        ...createAssetStocktakeVarianceScopeFilterFields(),
    ];
}
