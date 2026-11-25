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
                key: 'page.purchase_history.search_placeholder',
                fallback:
                    'Search PO number, supplier, warehouse, or product...',
            },
        ),
        ...createPurchasingReportScopeFilterFields(),
        createPurchaseOrderStatusFilterField(),
    ];
}
