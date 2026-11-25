import {
    createPurchaseOrderStatusCategoryFilterField,
    createPurchaseOrderStatusFilterField,
    createPurchasingReportScopeFilterFields,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createPurchaseOrderStatusReportFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'column.search', fallback: 'Search' },
            {
                key: 'page.purchase_order_status.search_placeholder',
                fallback:
                    'Search PO number, supplier, warehouse, or product...',
            },
        ),
        ...createPurchasingReportScopeFilterFields(),
        createPurchaseOrderStatusCategoryFilterField(),
        createPurchaseOrderStatusFilterField(),
    ];
}
