import {
    createInventoryReportScopeFilterFields,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createStockMovementReportFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'column.search', fallback: 'Search' },
            {
                key: 'filter.stock_movement_search_placeholder',
                fallback: 'Search product, category, warehouse, branch...',
            },
        ),
        ...createInventoryReportScopeFilterFields(true),
    ];
}
