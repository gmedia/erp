import {
    createInventoryReportScopeFilterFields,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createInventoryValuationFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'column.search', fallback: 'Search' },
            {
                key: 'filter.valuation_search_placeholder',
                fallback: 'Search product, category, warehouse, branch...',
            },
        ),
        ...createInventoryReportScopeFilterFields(),
    ];
}
