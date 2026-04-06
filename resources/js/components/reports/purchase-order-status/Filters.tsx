import {
    createDateRangeFilterFields,
    createSelectFilterField,
    createSupplierWarehouseProductFilterFields,
    createTextFilterField,
    purchaseOrderStatusOptions,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createPurchaseOrderStatusReportFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            'Search',
            'Search PO number, supplier, warehouse, or product...',
        ),
        ...createSupplierWarehouseProductFilterFields(),
        createSelectFilterField(
            'status_category',
            'Status Category',
            [
                { value: 'outstanding', label: 'Outstanding' },
                { value: 'partially_received', label: 'Partially Received' },
                { value: 'closed', label: 'Closed' },
            ],
            'All categories',
        ),
        createSelectFilterField(
            'status',
            'PO Status',
            purchaseOrderStatusOptions,
            'All statuses',
        ),
        ...createDateRangeFilterFields(),
    ];
}
