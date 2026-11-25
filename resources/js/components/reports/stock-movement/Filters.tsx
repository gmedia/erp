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
                key: 'page.stock_movement.search_placeholder',
                fallback: 'Search product, category, warehouse, branch...',
            },
        ),
        ...createInventoryReportScopeFilterFields(true),
    ];
}
