import {
    assetStocktakeVarianceResultOptions,
    createAssetStocktakeFilterField,
    createBranchFilterField,
    createSelectFilterField,
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
        createAssetStocktakeFilterField('Select a stocktake'),
        createBranchFilterField('Select a branch'),
        createSelectFilterField(
            'result',
            'Result',
            assetStocktakeVarianceResultOptions,
            'Select result',
        ),
    ];
}
