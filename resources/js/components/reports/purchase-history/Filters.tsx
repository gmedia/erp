import {
    createDateRangeFilterFields,
    createSupplierWarehouseProductFilterFields,
    purchaseOrderStatusOptions,
    createSelectFilterField,
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
        ...createSupplierWarehouseProductFilterFields(),
        createSelectFilterField(
            'status',
            'PO Status',
            purchaseOrderStatusOptions,
            'All statuses',
        ),
        ...createDateRangeFilterFields(),
    ];
}
