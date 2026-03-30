import {
    createAsyncSelectFilterField,
    createDateRangeFilterFields,
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
        createAsyncSelectFilterField(
            'supplier_id',
            'Supplier',
            '/api/suppliers',
            'All suppliers',
        ),
        createAsyncSelectFilterField(
            'warehouse_id',
            'Warehouse',
            '/api/warehouses',
            'All warehouses',
        ),
        createAsyncSelectFilterField(
            'product_id',
            'Product',
            '/api/products',
            'All products',
        ),
        createSelectFilterField(
            'status',
            'PO Status',
            [
                { value: 'draft', label: 'Draft' },
                { value: 'pending_approval', label: 'Pending Approval' },
                { value: 'confirmed', label: 'Confirmed' },
                { value: 'rejected', label: 'Rejected' },
                { value: 'partially_received', label: 'Partially Received' },
                { value: 'fully_received', label: 'Fully Received' },
                { value: 'cancelled', label: 'Cancelled' },
                { value: 'closed', label: 'Closed' },
            ],
            'All statuses',
        ),
        ...createDateRangeFilterFields(),
    ];
}
