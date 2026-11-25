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
            { key: 'column.search', fallback: 'Search' },
            {
                key: 'page.inventory_stocktake_variance.search_placeholder',
                fallback: 'Search stocktake, product, category, warehouse...',
            },
        ),
        createInventoryStocktakeReportFilterField(),
        ...createInventoryReportScopeFilterFields(),
        createInventoryStocktakeVarianceResultFilterField(),
        ...createDateRangeFilterFields(),
    ];
}
