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
                key: 'filter.stocktake_variance_search_placeholder',
                fallback: 'Search stocktake, product, category, warehouse...',
            },
        ),
        createInventoryStocktakeReportFilterField(),
        ...createInventoryReportScopeFilterFields(),
        createInventoryStocktakeVarianceResultFilterField(),
        ...createDateRangeFilterFields(),
    ];
}
