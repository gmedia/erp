import {
    createPurchaseOrderStatusFilterField,
    createPurchasingReportScopeFilterFields,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createPurchaseHistoryReportFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'column.search', fallback: 'Search' },
            {
                key: 'filter.purchase_search_placeholder',
                fallback:
                    'Search PO number, supplier, warehouse, or product...',
            },
        ),
        ...createPurchasingReportScopeFilterFields(),
        createPurchaseOrderStatusFilterField(),
    ];
}
