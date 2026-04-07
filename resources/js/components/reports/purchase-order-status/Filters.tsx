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
            'Search',
            'Search PO number, supplier, warehouse, or product...',
        ),
        ...createPurchasingReportScopeFilterFields(),
        createPurchaseOrderStatusCategoryFilterField(),
        createPurchaseOrderStatusFilterField(),
    ];
}
