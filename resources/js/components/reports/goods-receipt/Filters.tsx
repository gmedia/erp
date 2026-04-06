import {
    createDateRangeFilterFields,
    createSelectFilterField,
    createSupplierWarehouseProductFilterFields,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createGoodsReceiptReportFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            'Search',
            'Search GR number, PO number, supplier, warehouse, or product...',
        ),
        ...createSupplierWarehouseProductFilterFields(),
        createSelectFilterField(
            'status',
            'Status',
            [
                { value: 'draft', label: 'Draft' },
                { value: 'confirmed', label: 'Confirmed' },
                { value: 'cancelled', label: 'Cancelled' },
            ],
            'All statuses',
        ),
        ...createDateRangeFilterFields(),
    ];
}
