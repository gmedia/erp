import {
    createDateRangeFilterFields,
    createInventoryReportScopeFilterFields,
    createInventoryStocktakeReportFilterField,
    createInventoryStocktakeVarianceResultFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createInventoryStocktakeVarianceFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            'Search',
            'Search stocktake, product, category, warehouse...',
        ),
        createInventoryStocktakeReportFilterField(),
        ...createInventoryReportScopeFilterFields(),
        createInventoryStocktakeVarianceResultFilterField(),
        ...createDateRangeFilterFields(),
    ];
}
