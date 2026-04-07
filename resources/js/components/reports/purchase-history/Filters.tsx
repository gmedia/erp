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
            'Search',
            'Search PO number, supplier, warehouse, or product...',
        ),
        ...createPurchasingReportScopeFilterFields(),
        createPurchaseOrderStatusFilterField(),
    ];
}
